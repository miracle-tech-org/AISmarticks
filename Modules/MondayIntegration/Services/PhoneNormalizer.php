<?php

namespace Modules\MondayIntegration\Services;

/**
 * PhoneNormalizer Service
 * 
 * Provides methods for normalizing and comparing phone numbers
 * with support for international formats and partial matching.
 */
class PhoneNormalizer
{
    /**
     * Default minimum number of digits to match from right to left
     *
     * @var int
     */
    protected $defaultMinDigits = 8;
    
    /**
     * Country code map for common countries
     *
     * @var array
     */
    protected $countryCodeMap = [
        '1' => 'US/CA',   // USA/Canada
        '44' => 'GB',     // UK
        '49' => 'DE',     // Germany
        '33' => 'FR',     // France
        '34' => 'ES',     // Spain
        '39' => 'IT',     // Italy
        '972' => 'IL',    // Israel
        '91' => 'IN',     // India
        '86' => 'CN',     // China
        '52' => 'MX',     // Mexico
        '55' => 'BR',     // Brazil
    ];
    
    /**
     * Normalize a phone number by removing all non-numeric characters
     *
     * @param string $phoneNumber
     * @return string Normalized phone number (digits only)
     */
    public function normalize($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return '';
        }
        
        // Remove all non-numeric characters
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }
    
    /**
     * Extract the country code from a phone number if present
     *
     * @param string $phoneNumber
     * @return array [countryCode, nationalNumber]
     */
    public function extractCountryCode($phoneNumber)
    {
        $normalized = $this->normalize($phoneNumber);
        
        if (empty($normalized)) {
            return [null, ''];
        }
        
        // Check if the number starts with a plus sign or international prefix
        $hasPlus = strpos($phoneNumber, '+') === 0;
        $hasZeros = strpos($normalized, '00') === 0;
        
        // If it doesn't have international format, return as is
        if (!$hasPlus && !$hasZeros) {
            return [null, $normalized];
        }
        
        // Remove international prefix if present
        if ($hasZeros) {
            $normalized = substr($normalized, 2);
        }
        
        // Try to identify country code
        // Check 3-digit country codes
        foreach (array_keys($this->countryCodeMap) as $code) {
            if (strlen($code) === 3 && strpos($normalized, $code) === 0) {
                return [$code, substr($normalized, 3)];
            }
        }
        
        // Check 2-digit country codes
        foreach (array_keys($this->countryCodeMap) as $code) {
            if (strlen($code) === 2 && strpos($normalized, $code) === 0) {
                return [$code, substr($normalized, 2)];
            }
        }
        
        // Check 1-digit country codes
        foreach (array_keys($this->countryCodeMap) as $code) {
            if (strlen($code) === 1 && strpos($normalized, $code) === 0) {
                return [$code, substr($normalized, 1)];
            }
        }
        
        // If no country code was identified, assume the whole number is national
        return [null, $normalized];
    }
    
    /**
     * Format a phone number for display
     *
     * @param string $phoneNumber
     * @param bool $includeCountryCode
     * @return string
     */
    public function format($phoneNumber, $includeCountryCode = true)
    {
        [$countryCode, $nationalNumber] = $this->extractCountryCode($phoneNumber);
        
        if (empty($nationalNumber)) {
            return '';
        }
        
        // If we have a country code and it should be included
        $prefix = ($countryCode && $includeCountryCode) ? "+{$countryCode} " : '';
        
        // Format the national number based on length
        $length = strlen($nationalNumber);
        
        if ($length === 10) {
            // US/Canada style: (555) 123-4567
            return $prefix . '(' . substr($nationalNumber, 0, 3) . ') ' 
                . substr($nationalNumber, 3, 3) . '-' 
                . substr($nationalNumber, 6);
        } elseif ($length === 9) {
            // Common in many countries: 555-123-456
            return $prefix . substr($nationalNumber, 0, 3) . '-' 
                . substr($nationalNumber, 3, 3) . '-' 
                . substr($nationalNumber, 6);
        } elseif ($length === 8) {
            // Common in some countries: 5555-6666
            return $prefix . substr($nationalNumber, 0, 4) . '-' 
                . substr($nationalNumber, 4);
        } else {
            // For other lengths, just add a space every 4 digits
            return $prefix . implode(' ', str_split($nationalNumber, 4));
        }
    }
    
    /**
     * Compare two phone numbers using right-to-left matching
     *
     * @param string $phone1
     * @param string $phone2
     * @param int|null $minDigits Minimum number of matching digits required
     * @return array [isMatch, matchedDigits, confidenceScore]
     */
    public function compare($phone1, $phone2, $minDigits = null)
    {
        $normalized1 = $this->normalize($phone1);
        $normalized2 = $this->normalize($phone2);
        
        if (empty($normalized1) || empty($normalized2)) {
            return [false, 0, 0.0];
        }
        
        // Use default min digits if not specified
        $minDigits = $minDigits ?: $this->defaultMinDigits;
        
        // If the numbers are identical, it's a perfect match
        if ($normalized1 === $normalized2) {
            return [true, strlen($normalized1), 1.0];
        }
        
        // Compare digits from right to left
        $length1 = strlen($normalized1);
        $length2 = strlen($normalized2);
        $maxCompare = min($length1, $length2);
        $matchedDigits = 0;
        
        for ($i = 1; $i <= $maxCompare; $i++) {
            if (substr($normalized1, -$i, 1) === substr($normalized2, -$i, 1)) {
                $matchedDigits++;
            } else {
                break; // Stop at first non-matching digit
            }
        }
        
        // Calculate confidence score based on matched digits
        // We use a logarithmic scale to give more weight to more matches
        $confidenceScore = 0.0;
        
        if ($matchedDigits >= $minDigits) {
            // Base confidence on the proportion of matched digits to the longer number's length
            $maxLength = max($length1, $length2);
            $baseConfidence = $matchedDigits / $maxLength;
            
            // Apply a logarithmic scale to boost higher match counts
            // This gives more weight to matches with more digits
            $confidenceScore = min(1.0, $baseConfidence * (1 + log10(max(1, $matchedDigits - $minDigits + 1))));
        }
        
        return [
            $matchedDigits >= $minDigits,
            $matchedDigits,
            $confidenceScore
        ];
    }
    
    /**
     * Find the best match for a phone number from a list of candidates
     *
     * @param string $phoneNumber
     * @param array $candidates
     * @param int|null $minDigits
     * @return array|null [candidateValue, matchedDigits, confidenceScore]
     */
    public function findBestMatch($phoneNumber, array $candidates, $minDigits = null)
    {
        if (empty($phoneNumber) || empty($candidates)) {
            return null;
        }
        
        $bestMatch = null;
        $bestMatchedDigits = 0;
        $bestConfidence = 0.0;
        
        foreach ($candidates as $candidate) {
            [$isMatch, $matchedDigits, $confidence] = $this->compare($phoneNumber, $candidate, $minDigits);
            
            if ($isMatch && ($matchedDigits > $bestMatchedDigits || 
                ($matchedDigits === $bestMatchedDigits && $confidence > $bestConfidence))) {
                $bestMatch = $candidate;
                $bestMatchedDigits = $matchedDigits;
                $bestConfidence = $confidence;
            }
        }
        
        if ($bestMatch) {
            return [$bestMatch, $bestMatchedDigits, $bestConfidence];
        }
        
        return null;
    }
    
    /**
     * Set the default minimum number of digits to match
     *
     * @param int $digits
     * @return $this
     */
    public function setDefaultMinDigits($digits)
    {
        $this->defaultMinDigits = max(4, min(15, (int)$digits));
        return $this;
    }
    
    /**
     * Get the default minimum number of digits to match
     *
     * @return int
     */
    public function getDefaultMinDigits()
    {
        return $this->defaultMinDigits;
    }
    
    /**
     * Check if a phone number appears to be in WhatsApp format
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isWhatsAppFormat($phoneNumber)
    {
        // WhatsApp numbers typically have a country code
        // and often start with a + or have a specific format
        $normalized = $this->normalize($phoneNumber);
        
        if (empty($normalized)) {
            return false;
        }
        
        // Check if it starts with a plus sign
        $hasPlus = strpos($phoneNumber, '+') === 0;
        
        // Check if it starts with international prefix
        $hasZeros = strpos($normalized, '00') === 0;
        
        // Check if it has a reasonable length for an international number
        $hasReasonableLength = strlen($normalized) >= 10 && strlen($normalized) <= 15;
        
        return ($hasPlus || $hasZeros) && $hasReasonableLength;
    }
}
