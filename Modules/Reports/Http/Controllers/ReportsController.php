<?php

namespace Modules\Reports\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ReportsController extends Controller
{
    public function conversationsReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::conversations', [
            'filters' => $filters
        ]);
    }

    public function agentActivityReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::agent_activity', [
            'filters' => $filters
        ]);
    }

    public function firstResponseTimeReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::first_response_time', [
            'filters' => $filters
        ]);
    }

    public function conversationTagReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::conversation_tag', [
            'filters' => $filters
        ]);
    }

    public function customerInteractionReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::customer_interaction', [
            'filters' => $filters
        ]);
    }

    public function productivityReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));
        
        return view('reports::productivity', [
            'filters' => $filters
        ]);
    }

    public function satisfactionReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));
        
        return view('reports::satisfaction', [
            'filters' => $filters
        ]);
    }

    public function timeReport()
    {
        $this->checkPermissions();

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));
        
        return view('reports::time', [
            'filters' => $filters
        ]);
    }

	/**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $this->checkPermissions();
        
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            case 'report':
                
                switch ($request->report_name) {
                    case \Reports::REPORT_CONVERSATIONS:
                        $data = $this->getReportDataConversations($request);
                        break;

                    case \Reports::REPORT_PRODUCTIVITY:
                        $data = $this->getReportDataProductivity($request);
                        break;
                        
                    case \Reports::REPORT_SATISFACTION:
                        $data = $this->getReportSatisfaction($request);
                        break;

                    case \Reports::REPORT_TIME:
                        $data = $this->getReportTime($request);
                        break;

                    case \Reports::REPORT_AGENT_ACTIVITY:
                        $data = $this->getReportAgentActivity($request);
                        break;

                    case \Reports::REPORT_FIRST_RESPONSE_TIME:
                        $data = $this->getReportFirstResponseTime($request);
                        break;

                    case \Reports::REPORT_CONVERSATION_TAG:
                        $data = $this->getReportConversationTag($request);
                        break;

                    case \Reports::REPORT_CUSTOMER_INTERACTION:
                        $data = $this->getReportCustomerInteraction($request);
                        break;
                }
                $response['report'] = view('reports::partials/report_'.$request->report_name, $data)->render();
                $response['chart'] = $data['chart'];
                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }
        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    public function getReportDataConversations($request)
    {
        $data = [];

        // Total Conversations.
        $value = $this->countTotalConv($request);
        $data['metrics']['total']['value'] = $value;
        $data['metrics']['total']['change'] = $this->calcChange($value, $this->countTotalConv($request, true));

        // New Conversations.
        $value = $this->countNewConv($request);
        $data['metrics']['new']['value'] = $value;
        $data['metrics']['new']['change'] = $this->calcChange($value, $this->countNewConv($request, true));

        // Messages received.
        $value = $this->countMessages($request);
        $data['metrics']['messages']['value'] = $value;
        $data['metrics']['messages']['change'] = $this->calcChange($value, $this->countMessages($request, true));

        // Customers.
        $value = $this->countCustomers($request);
        $data['metrics']['customers']['value'] = $value;
        $data['metrics']['customers']['change'] = $this->calcChange($value, $this->countCustomers($request, true));

        // Conversations per Day.
        $convs_by_day = $this->getConvsByDay($request);

        $value = $this->countConvsPerDay($convs_by_day);
        $data['metrics']['conv_day']['value'] = $value;
        $data['metrics']['conv_day']['change'] = $this->calcChange($value, $this->countConvsPerDay($this->getConvsByDay($request, true)));

        $data['metrics']['busy_day']['value'] = $this->countBusyDay($convs_by_day);

        // Chart.
        $data['chart']['type'] = $request->chart['type'] ?? 'new_conv';

        $data['chart']['group_bys'] = $this->getChartGroupBys($request);
        $data['chart']['group_by'] = $this->getChartGroupBy($request, $data['chart']['group_bys']);

        switch ($data['chart']['type']) {
            case 'new_conv':
                $data['chart'] = $this->chartNewConv($data['chart'], $request);
                break;
            
            case 'messages':
                $data['chart'] = $this->chartMessages($data['chart'], $request);
                break;
        }

        // Tables.
        $data['table_customers'] = $this->tableCustomers($request);
        $data['table_tags'] = [];
        if (\Module::isActive('tags')) {
            $data['table_tags'] = $this->tableTags($request);
        }

        return $data;
    }

    public function getReportAgentActivity($request)
    {
        $data = [];

        // Number of conversations handled.
        $value = $this->countTotalClosedConv($request);
        $data['metrics']['conv_handled']['value'] = $value;
        $data['metrics']['conv_handled']['change'] = $this->calcChange($value, $this->countTotalClosedConv($request, true));

        // Responses made.
        $value = $this->countRepliesSent($request);
        $data['metrics']['responses_made']['value'] = $value;
        $data['metrics']['responses_made']['change'] = $this->calcChange($value, $this->countRepliesSent($request, true));

        // Average handling time
        $value = $this->calcAvgHoursHandling($request);
        $data['metrics']['average_handling']['value']  = $this->formatTime((int)$value);
        $data['metrics']['average_handling']['change'] = $this->calcChange($value, $this->calcAvgHoursHandling($request, true));

        // Chart.
        $data['chart'] = [];
        $data['chart'] = $this->chartAgentActivityData($data['chart'], $request);

        // Tables.
        $data['table_agents'] = $this->tableAgents($request);

        return $data;
    }

    public function getReportCustomerInteraction($request)
    {
        $reportData = $this->customerInteractionsReportData($request);
        error_log('REPORT_DATA: ' . json_encode($reportData));

        $data = [];

        // Interactions Per Customer.
        $value = $this->countCustomerInteractionsData($reportData['interactions_counts']['data'], 'count');
        $data['metrics']['interactions_counts']['value'] = $value;
        $data['metrics']['interactions_counts']['change'] = $this->calcChange($value, $this->countCustomerInteractionsData($reportData['interactions_counts']['prev'], 'count'));

        // Agents Responses times.
        $value = $reportData['agent_responses_times']['data'];

        $resp = 0;
        $avg = 0;
        if (is_array($value)) {
            $resp = array_sum(array_pluck($value, 'seconds'));
            $respCount = array_sum(array_pluck($value, 'countResp'));

            if ($respCount != 0) {
                $avg = $resp / $respCount;
            }
        }

        $valPrev = $reportData['agent_responses_times']['prev'];
        $respPrev = 0;
        $avgPrev = 0;
        if (is_array($valPrev)) {
            $respPrev = array_sum(array_pluck($valPrev, 'seconds'));
            $respCountPrev = array_sum(array_pluck($valPrev, 'countResp'));

            if ($respCountPrev != 0) {
                $avgPrev = $respPrev / $respCountPrev;
            }
        }
        $avgAsTime = $this->secondsToTime($avg);

        $data['metrics']['agent_responses_times']['value'] = $this->secondsToTime($resp);
        $data['metrics']['agent_responses_times']['change'] = $this->calcChange($resp, $respPrev);


        $data['metrics']['avg_agents_response_time']['value'] = $avgAsTime;
        $data['metrics']['avg_agents_response_time']['change'] = $this->calcChange($avg, $avgPrev);

        // Customers Response times.
        $value = $reportData['customers_response_time']['data'];

        $resp = 0;
        $avg = 0;
        if (is_array($value)) {
            $resp = array_sum(array_pluck($value, 'seconds'));
            $respCount = array_sum(array_pluck($value, 'countResp'));

            if ($respCount != 0) {
                $avg = $resp / $respCount;
            }
        }

        $valPrev = $reportData['customers_response_time']['prev'];
        $respPrev = 0;
        $avgPrev = 0;
        if (is_array($valPrev)) {
            $respPrev = array_sum(array_pluck($valPrev, 'seconds'));
            $respCountPrev = array_sum(array_pluck($valPrev, 'countResp'));

            if ($respCountPrev != 0) {
                $avgPrev = $respPrev / $respCountPrev;
            }
        }
        $avgAsTime = $this->secondsToTime($avg);

        $data['metrics']['customers_response_time']['value'] = $this->secondsToTime($resp);
        $data['metrics']['customers_response_time']['change'] = $this->calcChange($resp, $respPrev);


        $data['metrics']['avg_customers_response_time']['value'] = $avgAsTime;
        $data['metrics']['avg_customers_response_time']['change'] = $this->calcChange($avg, $avgPrev);

        // Resolution times
        $value = $this->countCustomerInteractionsData($reportData['resolution_times']['data'], 'seconds');
        $data['metrics']['resolution_times']['value']  = $this->secondsToTime($value);
        $data['metrics']['resolution_times']['change'] = $this->calcChange($value, $this->countCustomerInteractionsData($reportData['resolution_times']['prev'], 'seconds'));

        $tableData = $this->tableCustomerInteraction($reportData, $request);
        error_log('TABLE_DATA: ' . json_encode($tableData));

        // Chart.
        $data['chart'] = [];
        $data['chart']['report_data'] = $tableData;
        $data['chart']['full_report_data'] = $reportData;
        $data['chart']['labels'] = $this->customerIdToLabel(array_keys($tableData));
        $data['chart'] = $this->chartCustomerInteractionsData($data['chart'], $request);

        // Tables.
        $data['table_customer_interaction'] = $tableData;

        return $data;
    }

    public function getReportConversationTag($request)
    {
        $data = [];
        // Number of unique tags.
        $value = $this->countTags($request);
        $data['metrics']['count_tags']['value'] = $value;
        $data['metrics']['count_tags']['change'] = $this->calcChange($value, $this->countTags($request, true));

        // Number of tags used in conversations.
        $value = $this->countUsedTags($request);
        $data['metrics']['count_used_tags']['value'] = $value;
        $data['metrics']['count_used_tags']['change'] = $this->calcChange($value, $this->countTags($request, true));

        // Commonly used tag.
        $value = $this->namesCommonlyUsedTag($request);
        $data['metrics']['commonly_used_tag']['value'] = $value;

        // Chart.
        $data['chart'] = [];
        $data['chart'] = $this->chartConversationTagsData($data['chart'], $request);

        // Tables.
        $data['table_tags_data'] = $this->conversationTags($request)->get()->toArray();

        return $data;
    }

    public function getReportFirstResponseTime($request)
    {
        $data = [];

        // Number of conversations handled.
        $value = $this->countFirstResponseTime($request, 'user_id');
        if (is_array($value)) {
            $resp = array_sum(array_pluck($value, 'seconds'));
            $respCount = array_sum(array_pluck($value, 'countResp'));

            $avg = 0;
            if ($respCount != 0) {
                $avg = $resp / $respCount;
            }
        }
        $valPrev = $this->countFirstResponseTime($request, 'user_id', true);
        if (is_array($valPrev)) {
            $respPrev = array_sum(array_pluck($valPrev, 'seconds'));
            $respCountPrev = array_sum(array_pluck($valPrev, 'countResp'));

            $avgPrev = 0;
            if ($respCountPrev != 0) {
                $avgPrev = $respPrev / $respCountPrev;
            }
        }
        $respAsTime = $this->secondsToTime($resp);
        $avgAsTime = $this->secondsToTime($avg);

        $data['metrics']['responses_time']['value'] = $respAsTime;
        $data['metrics']['responses_time']['change'] = $this->calcChange($resp, $respPrev);

        // Responses made.
        $data['metrics']['avg_agents_response_time']['value'] = $avgAsTime;
        $data['metrics']['avg_agents_response_time']['change'] = $this->calcChange($avg, $avgPrev);

        // Chart.
        $data['chart'] = [];
        $data['chart'] = $this->chartFirstResponseTime($data['chart'], $request);

        // Tables.
        $data['table_agents'] = $this->tableFirstResponseTimeData($request, $value);
//        error_log('FRT_TABLE:' . json_encode($data['table_agents']));

        return $data;
    }

    public function getReportDataProductivity($request)
    {
        $data = [];

        // Customers Helped.
        $value = $this->countCustomersHelped($request);
        $data['metrics']['customers_helped']['value'] = $value;
        $data['metrics']['customers_helped']['change'] = $this->calcChange($value, $this->countCustomersHelped($request, true));

        // Rplies Sent.
        $value = $this->countRepliesSent($request);
        $data['metrics']['replies']['value'] = $value;
        $data['metrics']['replies']['change'] = $this->calcChange($value, $this->countRepliesSent($request, true));

        // Conversations per Day.
        $value = $this->countRepliesPerDay($request);
        $data['metrics']['replies_day']['value'] = $value;
        $data['metrics']['replies_day']['change'] = $this->calcChange($value, $this->countRepliesPerDay($request, true));

        // Total Conversations.
        $value = $this->countClosed($request);
        $data['metrics']['closed']['value'] = $value;
        $data['metrics']['closed']['change'] = $this->calcChange($value, $this->countClosed($request, true));

        // Resolved On First Reply.
        $metas_resolution = $this->getMetasResolution($request);
        $metas_resolution_prev = $this->getMetasResolution($request, true);
        $value = $this->countRfr($metas_resolution);
        $data['metrics']['rfr']['value'] = $value;
        $data['metrics']['rfr']['change'] = $this->calcChange($value, $this->countRfr($metas_resolution_prev ?: $metas_resolution));

        // Chart.
        $data['chart']['type'] = $request->chart['type'] ?? 'customers_helped';

        $data['chart']['group_bys'] = $this->getChartGroupBys($request);
        $data['chart']['group_by'] = $this->getChartGroupBy($request, $data['chart']['group_bys']);

        switch ($data['chart']['type']) {
            case 'customers_helped':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartCustomersHelpedData($request),
                    $this->chartCustomersHelpedData($request, true)
                );
                break;
            
            case 'replies':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartRepliesData($request),
                    $this->chartRepliesData($request, true)
                );
                break;   

            case 'closed':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartClosedData($request),
                    $this->chartClosedData($request, true)
                );
                break;
        }

        // Tables.
        $data['table_users'] = $this->tableUsers($request);

        $data['table_resolution_time'] = $this->tableResolutionTime($metas_resolution, $metas_resolution_prev ?: $metas_resolution);
        $data['table_replies_to_resolve'] = $this->tableRepliesToResolve($metas_resolution, $metas_resolution_prev ?: $metas_resolution);

        // Clean memory.
        unset($metas_resolution);
        unset($metas_resolution_prev);

        $metas_response = $this->getMetasResponse($request);
        $metas_response_prev = $this->getMetasResponse($request, true);

        $data['table_first_response_time'] = $this->tableFirstResponseTime($metas_response, $metas_response_prev ?: $metas_response);
        $data['table_response_time'] = $this->tableResponseTime($metas_response, $metas_response_prev ?: $metas_response);
        
        return $data;
    }

    public function calcChange($cur_value, $prev_value)
    {
        if ($cur_value) {
            return round(($cur_value - $prev_value) * 100 / $cur_value);
        } else {
            return 0;
        }
    }

    /**
     * Number of conversations touched (received, replied to, status changed, 
     * assigned, workflow activated on), excluding spam and deleted conversations.
     */
    public function countTotalConv($request, $prev = false)
    {
        //return Thread::distinct('conversation_id')->count('conversation_id');
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM);

        $this->applyFilter($query, $request, $prev, 'updated_at');

        return $query->count();
    }

    /**
     * Number of conversations created by customers or users, excluding spam and deleted conversations.
     */
    public function countNewConv($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM);
        
        $this->applyFilter($query, $request, $prev);

        return $query->count();
    }

    /**
     * Number of messages (emails) received from customers.
     */
    public function countMessages($request, $prev = false)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER);
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Number of customers who created or updated conversations.
     */
    public function countCustomers($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->distinct('customer_id');

        $this->applyFilter($query, $request, $prev);

        return $query->count('customer_id');
    }

    public function getConvsByDay($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev);

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $data;
    }

    /**
     * Average number of new or updated conversations per day.
     */
    public function countConvsPerDay($convs_by_day)
    {
        if (count($convs_by_day)) {
            return round($convs_by_day->sum('conv_count') / count($convs_by_day));
        } else {
            return 0;
        }
    }

    /**
     * Day of the week with the highest number of new conversations on average.
     */
    public function countBusyDay($convs_by_day)
    {
        $stats = [];

        foreach ($convs_by_day as $row) {
            $day = User::dateFormat($row['updated_date'], 'l');
            if (!isset($stats[$day])) {
                $stats[$day] = 0;
            }
            $stats[$day] += $row['conv_count'];
        }

        $busy_day = '-';
        $max = 0;
        foreach ($stats as $day => $conv_count) {
            if ($conv_count > $max) {
                $max = $conv_count;
                $busy_day = $day;
            }
        }
        return $busy_day;
    }

    public function prevDiffInDays($request)
    {
        $days = 0;

        $from = $request->filters['from'];
        $to = $request->filters['to'];

        if ($from && $to) {
            $from_carbon = Carbon::parse($from);
            $to_carbon = Carbon::parse($to);

            $days = $from_carbon->diffInDays($to_carbon);
        }

        return $days;
    }

    public function applyFilter($query, $request, $prev = false, $date_field = 'conversations.created_at', $date_field_to = '')
    {
        $from = $request->filters['from'];
        $to = $request->filters['to'];

        $user = auth()->user();

        if (!$date_field_to) {
            $date_field_to = $date_field;
        }

        if ($prev) {
            if ($from && $to) {
                $from_carbon = Carbon::parse($from);
                $to_carbon = Carbon::parse($to);

                $days = $from_carbon->diffInDays($to_carbon);

                if ($days) {
                    $from = $from_carbon->subDays($days)->format('Y-m-d');
                    $to = $to_carbon->subDays($days)->format('Y-m-d');
                }
            }
        }

        if (!empty($from)) {
            $query->where($date_field, '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if (!empty($to)) {
            $query->where($date_field_to, '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }
        if (!empty($request->filters['type'])) {
            $query->whereIn('conversations.type', $request->filters['type']);
        }
        if (!empty($request->filters['mailbox'])) {
            $query->where('conversations.mailbox_id', $request->filters['mailbox']);
        } elseif (!$user->isAdmin()) {
            $mailbox_ids = $user->mailboxesCanView(true)->pluck('id');
            if (count($mailbox_ids)) {
                $query->whereIn('conversations.mailbox_id', $mailbox_ids);
            }
        }
        if (!empty($request->filters['tag']) && \Module::isActive('tags')) {
            if (!strstr($query->toSql(), 'conversation_tag')) {
                $query->leftJoin('conversation_tag', function ($join) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                });
            }
            $query->whereIn('conversation_tag.tag_id', $request->filters['tag']);
        }

        // Custom fields.
        if (!empty($request->filters['custom_field']) && \Module::isActive('customfields')) {
            $custom_fields = \Reports::getCustomFieldFilters();

            if (count($custom_fields)) {
                $fieldAliasCounters = [];
                foreach ($custom_fields as $custom_field) {
                    if (!empty($request->filters['custom_field'][$custom_field->id])) {
                        if (!isset($fieldAliasCounters[$custom_field->id])) {
                            $fieldAliasCounters[$custom_field->id] = 0;
                        }
                        // Увеличиваем счетчик для текущего поля
                        $fieldAliasCounters[$custom_field->id] += 1;
                        $join_alias = 'ccf'.$custom_field->id.'_'.mt_rand(100, 999999);
                        $value = $request->filters['custom_field'][$custom_field->id];

                        $query->join('conversation_custom_field as '.$join_alias, function ($join) use ($custom_field, $value, $join_alias) {
                            $join->on('conversations.id', '=', $join_alias.'.conversation_id');
                            $join->where($join_alias.'.custom_field_id', $custom_field->id);
                            if ($custom_field->type == \Modules\CustomFields\Entities\CustomField::TYPE_MULTI_LINE) {
                                $join->where($join_alias.'.value', 'like', '%'.$value.'%');
                            } else {
                                $join->where($join_alias.'.value', $value);
                            }
                        });
                    }
                }
            }
        }
    }

    public function getChartGroupBys($request)
    {
        $group_bys = [];

        $from = Carbon::parse($request->filters['from']);
        $to = Carbon::parse($request->filters['to']);

        $days = $to->diffInDays($from);

        if ($days < 365) {
            $group_bys[] = 'd';
        }
        if ($days > 14) {
            $group_bys[] = 'w';
        }
        if ($days > 60) {
            $group_bys[] = 'm';
        }

        return $group_bys;
    }

    public function getChartGroupBy($request, $group_bys)
    {
        $group_by = $request->chart['group_by'] ?? '';

        if (!in_array($group_by, $group_bys)) {
            $group_by = $group_bys[0];
        }
        return $group_by;
    }
    
    public function chartNewConv($chart, $request)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart, 
            $this->chartNewConvData($request, $categories),
            $this->chartNewConvData($request, $categories, true)
        );

        return $chart;
    }

    public function chartAgentActivityData($chart, $request)
    {
        $chart['report'] = 'agent_activity';
        $labels = $this->chartLabels($chart, $request);
        $chart['labels'] = $labels;
        $chart = $this->chartAddDatasets(
            $chart,
            [
                $this->chatAgentActivityGetData($this->agentsClosedConv($request), 'closed_by_user_id', 'conversations_count'),
                $this->chatAgentActivityGetData($this->hoursHandlingTime($request), 'closed_by_user_id', 'avg_handling_time')
            ]

        );
        return $chart;
    }

    public function chartCustomerInteractionsData($chart, $request)
    {
        $chart['report'] = 'customer_interactions';
        $chart = $this->chartAddDatasets(
            $chart,
            []

        );
        return $chart;
    }

    public function chartConversationTagsData($chart, $request)
    {
        $chart['report'] = 'conversation_tag';
        $labels = $this->chartLabels($chart, $request);
        $chart['labels'] = $labels;
        $chart = $this->chartAddDatasets(
            $chart,
            array_pluck($this->conversationTags($request)->get()->toArray(), 'conv_count')

        );
        return $chart;
    }
    public function chartFirstResponseTime($chart, $request)
    {
        $chart['report'] = 'first_response_time';
        $data = $this->countFirstResponseTime($request, 'first_response_time');
        $labels = array_keys($data);

        $chartData = array_pluck($data, 'seconds');
        $seconds = [];
        foreach ($chartData as $value) {
            $seconds[] = $this->secondsToTime($value, 'hh.mm');
        }

        $chart['labels'] = $labels;
        $chart = $this->chartAddDatasets(
            $chart,
            [
                $seconds
            ]
        );
        return $chart;
    }

    public function chartMessages($chart, $request)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart, 
            $this->chartMessagesData($request, $categories),
            $this->chartMessagesData($request, $categories, true)
        );

        return $chart;
    }

    public function chartCategories($chart, $request, $names = false)
    {
        $categories = [];

        $from = Carbon::parse($request->filters['from']);
        $to = Carbon::parse($request->filters['to']);

        if ($to->lessThanOrEqualTo($from)) {
            return $categories;
        }

        while ($to->greaterThanOrEqualTo($from)) {
            if ($names) {
                switch ($chart['group_by']) {
                    case 'd':
                        $category = $to->format('M j');
                        break;
                    
                    case 'w':
                        $category = $to->format('M j');
                        break;

                    case 'm':
                        $category = $to->format('M j, Y');
                        break;
                }
            } else {
                $category = $to->format('Y-m-d');
            }
            array_unshift($categories, $category);
            switch ($chart['group_by']) {
                case 'd':
                    $to->subDay();
                    break;
                
                case 'w':
                    $to->subWeek();
                    break;

                case 'm':
                    $to->subMonth();
                    break;
            }
            
        }

        return $categories;
    }

    public function chartLabels($chart, $request)
    {
        $labels = [];
        switch ($chart['report']) {
            case 'agent_activity':
                $data = $this->agentsClosedConv($request);

                if (!count($data)) {
                    return $labels;
                }

                $users_ids = array_pluck($data, 'closed_by_user_id');
                $users = User::whereIn('id', $users_ids)->get();

                if (!count($users)) {
                    return $labels;
                }
                $labels = $users->map(function($user) {
                    return $user->first_name . ' ' . $user->last_name;
                })->toArray();
                break;
            case 'first_response_time':
                foreach ($this->firstAgentResponseTimeData($request) as $data) {
                    $labels[] = (new \DateTime($data['first_response_time']))->format('d M');
                }
                break;
            case 'conversation_tag':
                $data = $this->conversationTags($request)->get()->toArray();
                $labels = array_pluck($data, 'name');
                break;
            case 'customer_interactions':
                $customersIds = \Eventy::filter('threads.report_customers_id_by_filters', $request);

                // Customers data
                $users = Customer::select('id', \DB::raw('CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")) as name'))
                    ->whereIn('id', $customersIds)
                    ->get()
                    ->toArray();
                $labels = array_pluck($users, 'name');
                break;
        }


        return $labels;
    }

    public function chartNewConvData($request, $dates = [], $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev);

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('DATE(created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw("date_trunc('day', created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $this->chartDataByDays($stats, $dates, $request, $prev);
    }

    public function chartConvHandledData($request, $dates = [], $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev);

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('DATE(created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw("date_trunc('day', created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $this->chartDataByDays($stats, $dates, $request, $prev);
    }

    public function chartMessagesData($request, $dates = [], $prev = false)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $this->chartDataByDays($stats, $dates, $request, $prev);
    }


    public function chartDataByDays($stats, $dates, $request, $prev)
    {
        // Modify dates.
        if ($prev) {
            $days = $this->prevDiffInDays($request);
            foreach ($dates as $i => $date) {
                $dates[$i] = date('Y-m-d', strtotime($date) - $days*24*60*60);
            }
        }

        $data = [];
        foreach ($dates as $i => $date) {
            foreach ($stats as $stat) {
                if (($i == 0 && strtotime($stat['updated_date']) <= strtotime($date))
                    || (strtotime($stat['updated_date']) <= strtotime($date) && strtotime($stat['updated_date']) > strtotime($dates[$i-1]))
                ) {
                    $data[] = (int)$stat['conv_count'];
                    continue 2;
                }
            }
            $data[] = 0;
        }

        return $data;
    }

    public function tableCustomers($request)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER)
            ->groupBy('conversations.customer_id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $stats = $query->get(array(
            \DB::raw('conversations.customer_id'),
            \DB::raw('COUNT(*) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('conversations.customer_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('conversations.customer_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }

        $table_customers = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_customers = array_slice($table_customers, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_customers, 'customer_id');

        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_customers as $i => $table_customer) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_customer['customer_id']) {
                    $table_customers[$i]['customer'] = $customer;
                    continue 2;
                }
            }
        }

        return $table_customers;
    }

    public function tableAgents($request)
    {
        // Number of conversations handled
        $convHandled = $this->agentsClosedConv($request);

        // Agents data
        $userIDs = array_pluck($convHandled, 'closed_by_user_id');
        $users = User::whereIn('id', $userIDs)->get();

        // Responses made
        $responsesMade = $this->agentsResponsesMade($request);

        // Handling time
        $AHTs = $this->hoursHandlingTime($request);

        foreach ($convHandled as $i => $convHandledData) {
            if (empty($convHandled[$i]['user'])) {
                $convHandled[$i]['user'] = 0;
            }
            if (empty($convHandled[$i]['handling_time'])) {
                $convHandled[$i]['handling_time'] = 0;
            }
            if (empty($convHandled[$i]['responses'])) {
                $convHandled[$i]['responses'] = 0;
            }

            // Agents data
            foreach ($users as $user) {
                if ($user->id == $convHandledData['closed_by_user_id']) {
                    $convHandled[$i]['user'] = $user;
                    continue 1;
                }
            }

            // Average handling time
            foreach ($AHTs as $AHT) {
                if ($AHT['closed_by_user_id'] == $convHandledData['closed_by_user_id']) {
                    $convHandled[$i]['handling_time'] = $this->formatTime((int)$AHT['avg_handling_time']);
                    continue 1;
                }
            }

            // Responses messages
            foreach ($responsesMade as $responseMade) {
                if ($responseMade['created_by_user_id'] == $convHandledData['closed_by_user_id']) {
                    $convHandled[$i]['responses'] = $responseMade['responses_count'];
                    continue 1;
                }
            }
        }

        return $convHandled;
    }

    public function tableCustomerInteraction($data, $request)
    {
        $usersId = \Eventy::filter('threads.report_customers_id_by_filters', $request);
        error_log('USERS_IDS: ' . json_encode($usersId));

        $agentResponsesTimes = $data['agent_responses_times']['data'];
        $customerResponsesTimes = $data['customers_response_time']['data'];
        $resolutionTimes = $data['resolution_times']['data'];

        // Customers data
        $users = Customer::select('id', \DB::raw('CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")) as name'))
            ->whereIn('id', $usersId)
            ->pluck('name', 'id')
            ->toArray();
        error_log('USERS_DATA: ' . json_encode($users));

        $interactionsCount =  \Eventy::filter('threads.count_interactions_by_customer', $users, $request);
        $agentThreads =  \Eventy::filter('threads.count_messages_by_customer', $users, $request, 'agent');
        $customerThreads =  \Eventy::filter('threads.count_messages_by_customer', $users, $request, 'customer');

        $tableData = [];
        foreach ($users as $id => $name) {
            $filterData = [];

            if (empty($id)) {
                continue;
            }

            if (empty($tableData[$id]['name'])) {
                $tableData[$id]['name'] = $name;
            }
            if (empty($tableData[$id]['interaction_count'])) {
                $tableData[$id]['interaction_count'] = 0;
            }
            if (empty($tableData[$id]['agent_responses_times'])) {
                $tableData[$id]['agent_responses_times'] = $this->secondsToTime(0);
            }
            if (empty($tableData[$id]['customers_response_time'])) {
                $tableData[$id]['customers_response_time'] = $this->secondsToTime(0);
            }
            if (empty($tableData[$id]['resolution_times'])) {
                $tableData[$id]['resolution_times'] = $this->secondsToTime(0);
            }

            // Customer interactions data
            $tableData[$id]['interaction_count'] = !empty($interactionsCount) ? $interactionsCount[$id] : 0;

            // Agent first responses data
            if (!empty($agentResponsesTimes)) {
                $filterData = $this->reportArrayDataFilter($agentResponsesTimes, 'array_key', $id);

                if (!empty($filterData)) {
                    $tableData[$id]['agent_responses_times'] = $this->secondsToTime($filterData['seconds']);
                }
            }

            // Customer first responses data
            if (!empty($customerResponsesTimes)) {
                $filterData = $this->reportArrayDataFilter($customerResponsesTimes, 'array_key', $id);

                if (!empty($filterData)) {
                    $tableData[$id]['customers_response_time'] = $this->secondsToTime($filterData['seconds']);
                }
            }

            // Customer resolution times
            if (!empty($resolutionTimes)) {
                $filterData = $this->reportArrayDataFilter($resolutionTimes, 'customer_id', $id);

                if (!empty($filterData)) {
                    $tableData[$id]['resolution_times'] = $this->secondsToTime($filterData['seconds']);
                }
            }

            $tableData[$id]['agent_threads'] = !empty($agentThreads) ? $agentThreads[$id] : 0;
            $tableData[$id]['customer_threads'] = !empty($customerThreads) ? $customerThreads[$id] : 0;
        }

        return $tableData;
    }

    public function reportArrayDataFilter($data, $field, $needle)
    {
        $result = array_filter($data, function($item, $key) use ($field, $needle) {
            if ($field == 'array_key') {
                return $key == $needle;
            }

            return isset($item[$field]) && $item[$field] == $needle;
        }, ARRAY_FILTER_USE_BOTH);

        return !empty($result) ? array_values($result)[0] : [];

    }

    public function tableTags($request)
    {
        // Ideally created_at field is need in conversation_tag.
        $query = Conversation::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->leftJoin('conversation_tag', function ($join) {
                $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
            })
            ->groupBy('conversation_tag.tag_id');

        $this->applyFilter($query, $request, false);

        $stats = $query->get(array(
            \DB::raw('conversation_tag.tag_id'),
            \DB::raw('COUNT(*) as conv_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('conversation_tag.tag_id'),
        //         \DB::raw('COUNT(*) as conv_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('conversation_tag.tag_id'),
        //         \DB::raw('COUNT(*) conv_count')
        //     ));
        // }

        $table_tags = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_tags = array_slice($table_tags, 0, \Reports::MAX_TABLE_ITEMS);

        $tag_ids = array_pluck($table_tags, 'tag_id');

        $tags = \Modules\Tags\Entities\Tag::whereIn('id', $tag_ids)->get();

        foreach ($table_tags as $i => $table_tag) {
            foreach ($tags as $tag) {
                if ($tag->id == $table_tag['tag_id']) {
                    $table_tags[$i]['tag'] = $tag;
                    continue 2;
                }
            }
        }

        return $table_tags;
    }

    // Difficult to group by workflow_id in thread's meta.
    /*public function tableWorkflows($request)
    {
        $query = Thread::where('threads.type', Thread::TYPE_LINEITME)
            ->whereIn('threads.action_type', [\Workflow::ACTION_TYPE_AUTOMATIC_WORKFLOW, \Workflow::ACTION_TYPE_MANUAL_WORKFLOW])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('conversations.customer_id'),
                \DB::raw('COUNT(*) as messages_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw('conversations.customer_id'),
                \DB::raw('COUNT(*) messages_count')
            ));
        }

        $table_customers = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_customers = array_slice($table_customers, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_customers, 'customer_id');

        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_customers as $i => $table_customer) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_customer['customer_id']) {
                    $table_customers[$i]['customer'] = $customer;
                    continue 2;
                }
            }
        }

        return $table_customers;
    }*/

    /**
     * Customers helped.
     */
    public function countCustomersHelped($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->distinct('conversations.customer_id');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count('conversations.customer_id');
    }

    /**
     * Replies sent.
     */
    public function countRepliesSent($request, $prev = false)
    {
        return $this->responsesMade($request, $prev)->count();
    }

    /**
     * Count Tags.
     */
    public function countUsedTags($request, $prev = false)
    {
        $data = $this->getConversationTagsBy($request, $prev, 'conv_count'); // id, name, conv_count
        if (isset($data)) {
            return array_sum($data);
        }
        return 0;
    }

    /**
     * Count Tags.
     */
    public function countTags($request, $prev = false)
    {
        $data = $this->conversationTags($request, $prev)->get()->toArray();

        if (!empty($data)) {
            return count($data);
        }
        return 0;
    }

    /**
     * Name Commonly Used Tag.
     */
    public function namesCommonlyUsedTag($request)
    {
        $top = [];
        $data = $this->conversationTags($request, false)->get()->toArray();

        if (!empty($data)) {
            $maxConvCount = max(array_column($data, 'conv_count'));
            $top = array_filter($data, function($item) use ($maxConvCount) {
                return $item['conv_count'] == $maxConvCount;
            });
        }

        return $top;
    }

    /**
     * Responses made
     */
    public function responsesMade($request, $prev = false, $date_field = 'threads.created_at')
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.created_by_user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, $date_field);

        return $query;
    }

    /**
     * Conversations Tags
     */
    public function conversationTags($request, $prev = false)
    {
        $query = Conversation::select(
            'tags.id',
            'tags.name',
            'tags.color',
            \DB::raw('COUNT(conversation_tag.conversation_id) as conv_count'),
            \DB::raw('COUNT(DISTINCT conversations.created_by_customer_id) as unique_customer_count')
        )
            ->join('conversation_tag', function ($join) {
                $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
            })
            ->join('tags', function ($join) {
                $join->on('conversation_tag.tag_id', '=', 'tags.id');
            });

        $this->applyFilter($query, $request, $prev);

        $query = $query->groupBy('tags.id')->orderBy('conv_count', 'desc');

        return $query;
    }

    /**
     * Conversations Tags By Field
     */
    public function getConversationTagsBy($request, $prev, $by = 'conv_count')
    {
        $fullData = [];
        $data = $this->conversationTags($request, $prev)->get()->toArray();

        if (isset($data)) {
            switch ($by) {
                case 'conv_count':
                    $fullData = array_pluck($data, 'conv_count');
                    break;
                default:
                    foreach ($data as $key => $value) {
                        $fullData[$value[$by]] = $value;
                        unset($fullData[$value[$by]][$by]);
                    }
            }
        }

        return $fullData;
    }

    /**
     * AGENTS responses made
     */
    public function agentsResponsesMade($request)
    {
        $query = $this->responsesMade($request)->groupBy('threads.created_by_user_id');

//        $this->applyFilter($query, $request, false, 'threads.created_at');

        $stats = $query->get(array(
            \DB::raw('threads.created_by_user_id'),
            \DB::raw('COUNT(*) as responses_count')
        ))
            ->sortBy('responses_count')
            ->reverse()
            ->toArray();
        return array_slice($stats, 0, \Reports::MAX_TABLE_ITEMS);
    }

    public function countRepliesPerDay($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(*) as thread_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(*) thread_count')
            ));
        }

        if (count($data)) {
            return round($data->sum('thread_count') / count($data));
        } else {
            return 0;
        }
    }

    /**
     * Closed conversations.
     */
    public function countClosed($request, $prev = false)
    {
        $query = Thread::where(function ($q) {
                $q->where(function ($q2) {
                        $q2->where('threads.type', Thread::TYPE_LINEITEM)
                            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED);
                    })
                    ->orWhere('threads.type', Thread::TYPE_MESSAGE)
                    ->orWhere('threads.type', Thread::TYPE_NOTE);
            })
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('threads.created_by_user_id', '!=', null)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->distinct('conversations.id');
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count('conversations.id');
    }


    public function getMetasResolution($request, $prev = false)
    {
        $query = Conversation::where('meta', '!=', '');
        
        $this->applyFilter($query, $request, $prev, 'created_at', 'closed_at');

        return $query->pluck('meta');
    }

    public function getMetasResponse($request, $prev = false)
    {
        $robots_ids = User::getRobotsCondition()->pluck('id')->toArray();

        $query = Conversation::join('threads', function ($join) {
                $join->on('threads.conversation_id', '=', 'conversations.id');
            })
            ->where('conversations.meta', '!=', '')
            ->whereNotIn('threads.created_by_user_id', $robots_ids)
            ->where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->distinct('conversations.id');
        
        $this->applyFilter($query, $request, $prev, 'conversations.created_at', 'threads.created_at');

        $metas = $query->pluck('conversations.meta');

        // By some reason it's not casted automatically into array.
        if (isset($metas[0]) && !is_array($metas[0])) {
            foreach ($metas as $i => $meta) {
                $metas[$i] = json_decode($meta, true);
            }
        }

        return $metas;
    }

    /**
     * Conversations resolved on first reply.
     */
    public function countRfr($metas_resolution)
    {
        $value = 0;

        foreach ($metas_resolution as $meta) {
            if (!empty($meta['rpt']['rfr'])) {
                $value++;
            }
        }

        return $value;
    }

    public function chartAddSeries($chart, $data, $data_prev)
    {
        $chart['series'][] = [
            'name' => __('Current'),
            'fillOpacity' => '0.3',
            'data' => $data
        ];
        $chart['series'][] = [
            'name' => __('Previous'),
            'lineColor' => 'rgb(137,150,163)',
            //'lineWidth' => '1',
            'fillOpacity' => '0',
            'data' => $data_prev
        ];

        return $chart;
    }

    public function chartAddDatasets($chart, $data)
    {
        switch ($chart['report']) {
            case 'agent_activity':
                $chart['datasets'][] = [
                    'label' => __( "Conversations Handled" ),
                    'data' => $data[0],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ];
                $chart['datasets'][] = [
                    'label' => __( "Average Handling Time" ),
                    'data' => $data[1],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ];
                break;
            case 'csat_score':
                $chart['datasets'][] = [
                    'label' => __( "CSAT Score" ),
                    'data' => $data[0],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ];
                break;
            case 'first_response_time':
                $chart['datasets'][] = [
                    'label' => __( "First Response Time" ),
                    'data' => $data[0],
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1,
                ];
                break;
            case 'conversation_tag':
                $chart['datasets'][] = [
                    'label' => __( "Tag Count" ),
                    'data' => $data,
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 1,
                ];
                break;
            case 'customer_interactions':
                $interaction_count = [];
                $avg_agent_response_time = [];
                $avg_resolution_time = [];
                $data = $chart['report_data'];
                $fullData = $chart['full_report_data'];

                if (!empty($data)) {
                    // interaction count
                    $interaction_count = array_pluck($data, 'interaction_count');

                    if (!empty($fullData)) {
                        $customersIds = array_keys($data);
                        foreach ($customersIds as $id) {
                            // avg response time
                            $responseI = $fullData['agent_responses_times']['data'];
                            $avg = 0;
                            if (!empty($responseI) && !empty($responseI[$id]) && $responseI[$id]['countResp'] != 0) {
                                $avg = $responseI[$id]['seconds'] / $responseI[$id]['countResp'];
                            }
                            $data[$id]['agent_responses_times'] = $this->secondsToTime($avg, 'hh.mm');

                            // avg resolution time
                            $resolutionI = $fullData['resolution_times']['data'];
                            $resolutionAvg = 0;
                            if (!empty($resolutionI) && !empty($resolutionI[$id])) {
                                $resolutionAvg = $resolutionI[$id]['seconds'] / $resolutionI[$id]['count'];
                            }
                            $data[$id]['resolution_times'] = $this->secondsToTime($resolutionAvg, 'hh.mm');
                        }
                        $avg_agent_response_time = array_pluck($data, 'agent_responses_times');
                        $avg_resolution_time = array_pluck($data, 'resolution_times');
                    }

                }

                $chart['datasets'][] = [
                    'label' => __( "Interaction Count" ),
                    'data' => $interaction_count,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ];
                $chart['datasets'][] = [
                    'label' => __( "Average Response Time" ),
                    'data' => $avg_agent_response_time,
                    'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
                    'borderColor' => 'rgba(255, 206, 86, 1)',
                    'borderWidth' => 1,
                ];
                $chart['datasets'][] = [
                    'label' => __( "Average Resolution Time" ),
                    'data' => $avg_resolution_time,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ];
                break;
        }

        return $chart;
    }

    public function getChart($chart, $request, $data, $data_prev)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart, 
            $this->chartDataByDays($data, $categories, $request, false),
            $this->chartDataByDays($data_prev, $categories, $request, true)
        );

        return $chart;
    }

    public function chartCustomersHelpedData($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.created_by_user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(DISTINCT conversations.customer_id) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(DISTINCT conversations.customer_id) conv_count')
            ));
        }

        return $data;
    }

    public function chartRepliesData($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.created_by_user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $data;
    }

    public function chartClosedData($request, $prev = false)
    {
        $query = Thread::where(function ($q) {
                $q->where(function ($q2) {
                        $q2->where('threads.type', Thread::TYPE_LINEITEM)
                            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED);
                    })
                    ->orWhere('threads.type', Thread::TYPE_MESSAGE)
                    ->orWhere('threads.type', Thread::TYPE_NOTE);
            })
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('threads.created_by_user_id', '!=', null)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(DISTINCT conversations.id) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(DISTINCT conversations.id) conv_count')
            ));
        }

        return $data;
    }

    public function tableUsers($request)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.created_by_user_id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $stats = $query->get(array(
            \DB::raw('threads.created_by_user_id'),
            \DB::raw('COUNT(*) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }

        $table_users = $stats->sortBy('messages_count')->reverse()->toArray();
        //$table_users = array_slice($table_users, 0, \Reports::MAX_TABLE_ITEMS);

        // Get other users metrics.

        // Closed.
        $query = Thread::where(function ($q) {
                $q->where(function ($q2) {
                        $q2->where('threads.type', Thread::TYPE_LINEITEM)
                            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED);
                    })
                    ->orWhere('threads.type', Thread::TYPE_MESSAGE)
                    ->orWhere('threads.type', Thread::TYPE_NOTE);
            })
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            //->whereIn('threads.created_by_user_id', $user_ids)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.created_by_user_id');
        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');
        $stats = $query->get(array(
            \DB::raw('threads.created_by_user_id'),
            \DB::raw('COUNT(DISTINCT conversations.id) as messages_count')
        ));

        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }
        $closed_stats = $stats->toArray();
        $table_users = $this->addToTable($table_users, $closed_stats, 'closed');

        $user_ids = array_pluck($table_users, 'created_by_user_id');

        // Add new rows to the table.
        // This need to be done because user may have no replies
        // but may close some conversations via notes.
        foreach ($closed_stats as $i => $list_item) {
            if (in_array($list_item['created_by_user_id'], $user_ids)) {
                continue;
            }
            $list_item['closed'] = $list_item['messages_count'];
            $list_item['messages_count'] = 0;
            $table_users[] = $list_item;
        }

        // Form table_users.
        $user_ids = array_pluck($table_users, 'created_by_user_id');

        $users = User::whereIn('id', $user_ids)->get();

        foreach ($table_users as $i => $table_user) {
            foreach ($users as $user) {
                if ($user->id == $table_user['created_by_user_id']) {
                    $table_users[$i]['user'] = $user;
                    continue 2;
                }
            }
            unset($table_users[$i]);
        }

        // Customers helped.
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->whereIn('threads.created_by_user_id', $user_ids)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.created_by_user_id');
        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');
        $stats = $query->get(array(
            \DB::raw('threads.created_by_user_id'),
            \DB::raw('COUNT(DISTINCT conversations.customer_id) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(DISTINCT conversations.customer_id) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(DISTINCT conversations.customer_id) messages_count')
        //     ));
        // }
        $table_users = $this->addToTable($table_users, $stats->toArray(), 'customers_helped');

        return $table_users;
    }

    public function tableFirstResponseTime($metas, $metas_prev)
    {
        $table = $this->getTimeTablePattern();

        return $this->buildTimeTable($table, 'frt', $metas, $metas_prev);
    }

    public function tableResponseTime($metas, $metas_prev)
    {
        $table = $this->getTimeTablePattern();

        return $this->buildTimeTable($table, 'rst', $metas, $metas_prev);
    }

    public function getTimeTablePattern()
    {
        return [
            [
                'from' => 0,
                'to' => 15*60,
                'title' => __(':value min', ['value' => '< 15']),
            ], [
                'from' => 15*60,
                'to' => 30*60,
                'title' => __(':value min', ['value' => '15-30']),
            ], [
                'from' => 30*60,
                'to' => 60*60,
                'title' => __(':value min', ['value' => '30-60']),
            ], [
                'from' => 1*3600,
                'to' => 2*3600,
                'title' => __(':value hours', ['value' => '1-2']),
            ], [
                'from' => 2*3600,
                'to' => 3*3600,
                'title' => __(':value hours', ['value' => '2-3']),
            ], [
                'from' => 3*3600,
                'to' => 6*3600,
                'title' => __(':value hours', ['value' => '3-6']),
            ], [
                'from' => 6*3600,
                'to' => 12*3600,
                'title' => __(':value hours', ['value' => '6-12']),
            ], [
                'from' => 12*3600,
                'to' => 24*3600,
                'title' => __(':value hours', ['value' => '12-24']),
            ], [
                'from' => 1*3600*24,
                'to' => 2*3600*24,
                'title' => __(':value days', ['value' => '1-2']),
            ], [
                'from' => 2*3600*24,
                'to' => 2147483647,
                'title' => __(':value days', ['value' => '> 2']),
            ],
        ];
    }

    public function tableResolutionTime($metas, $metas_prev)
    {
        $table = $this->getTimeTablePattern();

        return $this->buildTimeTable($table, 'rnt', $metas, $metas_prev);
    }

    public function tableRepliesToResolve($metas, $metas_prev)
    {
        $table = [
            [
                'from' => 1,
                'to' => 2,
                'title' => '1',
            ], [
                'from' => 2,
                'to' => 3,
                'title' => '2',
            ], [
                'from' => 3,
                'to' => 4,
                'title' => '3',
            ], [
                'from' => 4,
                'to' => 5,
                'title' => '4',
            ], [
                'from' => 5,
                'to' => 2147483647,
                'title' => '> 5',
            ],
        ];

        $values = [];
        $count = 0;
        $count_prev = 0;
        $meta_name = 'rtr';

        foreach ($table as $i => $row) {
            foreach ($metas as $meta) {
                if (!empty($meta['rpt'][$meta_name])
                    && (int)$meta['rpt'][$meta_name] >= $row['from']
                    && (int)$meta['rpt'][$meta_name] < $row['to']
                ) {
                    $table[$i]['value'] = ($table[$i]['value'] ?? 0) + 1;
                    $count++;
                    $values[] = (int)$meta['rpt'][$meta_name];
                }
            }
        }
        foreach ($table as $i => $row) {
            foreach ($metas_prev as $meta) {
                if (!empty($meta['rpt'][$meta_name])
                    && (int)$meta['rpt'][$meta_name] >= $row['from']
                    && (int)$meta['rpt'][$meta_name] < $row['to']
                ) {
                    $table[$i]['value_prev'] = ($table[$i]['value_prev'] ?? 0) + 1;
                    $count_prev++;
                }
            }
        }
        
        // Calc percents and remove empty rows.
        foreach ($table as $i => $row) {
            if (empty($row['value']) /*&& empty($row['value_prev'])*/) {
                unset($table[$i]);
                continue;
            }
            if ($count) {
                $table[$i]['value_percent'] = round(100 * ($table[$i]['value'] ?? 0) / $count);
            } else {
                $table[$i]['value_percent'] = 0;
            }
            if ($count_prev) {
                $table[$i]['value_percent_prev'] = round(100 * ($table[$i]['value_prev'] ?? 0) / $count_prev);
            } else {
                $table[$i]['value_percent_prev'] = 0;
            }
        }

        // Calculate change.
        foreach ($table as $i => $row) {
            $table[$i]['change'] = $this->calcChange($row['value_percent'] ?? 0, $row['value_percent_prev'] ?? 0);
        }

        // Add average.
        if (count($values)) {
            $table[-1] = number_format(array_sum($values) / count($values), 2);
        } else {
            $table[-1] = 0;
        }

        return $table;
    }

    public function buildTimeTable($table, $meta_name, $metas, $metas_prev)
    {
        $values = [];
        $count = 0;
        $count_prev = 0;

        foreach ($table as $i => $row) {
            foreach ($metas as $meta) {
                if (!empty($meta['rpt'][$meta_name])
                    && (int)$meta['rpt'][$meta_name] >= $row['from']
                    && (int)$meta['rpt'][$meta_name] < $row['to']
                ) {
                    $table[$i]['value'] = ($table[$i]['value'] ?? 0) + 1;
                    $count++;
                    $values[] = (int)$meta['rpt'][$meta_name];
                }
            }
        }
        foreach ($table as $i => $row) {
            foreach ($metas_prev as $meta) {
                if (!empty($meta['rpt'][$meta_name])
                    && (int)$meta['rpt'][$meta_name] >= $row['from']
                    && (int)$meta['rpt'][$meta_name] < $row['to']
                ) {
                    $table[$i]['value_prev'] = ($table[$i]['value_prev'] ?? 0) + 1;
                    $count_prev++;
                }
            }
        }

        // Calc percents and remove empty rows.
        foreach ($table as $i => $row) {
            if (empty($row['value']) /*&& empty($row['value_prev'])*/) {
                unset($table[$i]);
                continue;
            }
            if ($count) {
                $table[$i]['value_percent'] = round(100 * ($table[$i]['value'] ?? 0) / $count);
            } else {
                $table[$i]['value_percent'] = 0;
            }
            if ($count_prev) {
                $table[$i]['value_percent_prev'] = round(100 * ($table[$i]['value_prev'] ?? 0) / $count_prev);
            } else {
                $table[$i]['value_percent_prev'] = 0;
            }
        }

        // Calculate change.
        foreach ($table as $i => $row) {
            $table[$i]['change'] = $this->calcChange($row['value_percent'] ?? 0, $row['value_percent_prev'] ?? 0);
        }

        // Add average.
        $table[-1] = $this->formatTime(\Reports::getMedianValue($values));

        return $table;
    }

    public function formatTime($time)
    {
        $days = floor($time / (3600 * 24));
        $hours = floor(($time - $days*3600*24) / 3600);
        $minutes = floor(floor($time / 60) % 60);

        $result = __(':value min', ['value' => $minutes]);
        if ($hours) {
            $result = __(':value h', ['value' => $hours]).' '.$result;
        }
        if ($days) {
            $result = __(':value d', ['value' => $days]).' '.$result;
        }
        return $result;
    }

    public function addToTable($table, $list, $col_name)
    {
        foreach ($table as $i => $table_item) {
            foreach ($list as $item) {
                if ($item['created_by_user_id'] == $table_item['created_by_user_id']) {
                    $table[$i][$col_name] = $item['messages_count'];
                    continue 2;
                }
            }
            $table[$i][$col_name] = 0;
        }
        return $table;
    }

    public function getReportSatisfaction($request)
    {
        $data = [];

        // Great.
        $great = $this->countGreat($request);
        $data['metrics']['great']['value'] = $great;
        $great_prev = $this->countGreat($request, true);
        $data['metrics']['great']['change'] = $this->calcChange($great, $great_prev);

        // Okay.
        $okay = $this->countOkay($request);
        $data['metrics']['okay']['value'] = $okay;
        $okay_prev = $this->countOkay($request, true);
        $data['metrics']['okay']['change'] = $this->calcChange($okay, $okay_prev);

        // Not Good.
        $notgood = $this->countNotgood($request);
        $data['metrics']['notgood']['value'] = $notgood;
        $notgood_prev = $this->countNotgood($request, true);
        $data['metrics']['notgood']['change'] = $this->calcChange($notgood, $notgood_prev);

        // Ratings.
        $ratings = (int)$data['metrics']['great']['value'] + (int)$data['metrics']['okay']['value'] +  (int)$data['metrics']['notgood']['value'];
        $data['metrics']['ratings']['value'] = $ratings;
        $ratings_prev = (int)$great_prev + (int)$okay_prev + (int)$notgood_prev;
        $data['metrics']['ratings']['change'] = $this->calcChange($ratings, $ratings_prev);

        // Satisfaction score.
        $satscore = 0;
        if ((int)$ratings) {
            $satscore = ceil(($great*100/$ratings) - ($notgood*100/$ratings));
        }
        $data['metrics']['satscore']['value'] = $satscore;
        $satscore_prev = 0;
        if ((int)$ratings_prev) {
            $satscore_prev = ceil(($great_prev*100/$ratings_prev) - ($notgood_prev*100/$ratings_prev));
        }
        $data['metrics']['satscore']['change'] = $this->calcChange($satscore, $satscore_prev);

        // Chart.
        $data['chart'] = [];
        $data['chart'] = $this->chartCSATScore($data['chart'], $request);

//        if (!$ratings) {
//            $ratings = 1;
//        }
//        $data['chart']['series'][] = [
//            'data' => [
//                [
//                    'name'      => __('Good'),
//                    'y'         => round($great*100/$ratings),
//                    'selected'  => true,
//                    'color'     => 'rgb(83,185,97)'
//                ],
//                [
//                    'name'      => __('Okay'),
//                    'y'         => round($okay*100/$ratings),
//                    'color'     => 'rgb(147,161,175)'
//                ],
//                [
//                    'name'      => __('Not Good'),
//                    'y'         => round($notgood*100/$ratings),
//                    'color'     => 'rgb(212,63,58)'
//                ],
//            ]
//        ];

        // Tables.
        $data['table_ratings'] = $this->tableRatings($request);

        return $data;
    }

    /**
     * Ratings.
     */
    public function countRatings($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', '>', 0)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Great.
     */
    public function countGreat($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_GREAT)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Okay.
     */
    public function countOkay($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_OKAY)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Not good.
     */
    public function countNotgood($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_BAD)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Not good.
     */
    public function countSatscore($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_BAD)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    public function tableRatings($request)
    {
        $query = Thread::select(['conversations.id', 'conversations.number', 'threads.created_by_user_id', 'conversations.customer_id', 'threads.created_at', 'threads.rating', 'threads.rating_comment'])
            ->where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', '>', 0)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->orderBy('threads.created_at', 'desc');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $table_ratings = $query->get()->toArray();

        $user_ids = array_pluck($table_ratings, 'created_by_user_id');
        $users = User::whereIn('id', $user_ids)->get();
        foreach ($table_ratings as $i => $table_rating) {
            foreach ($users as $user) {
                if ($user->id == $table_rating['created_by_user_id']) {
                    $table_ratings[$i]['user'] = $user;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        $customer_ids = array_pluck($table_ratings, 'customer_id');
        $customers = Customer::whereIn('id', $customer_ids)->get();
        foreach ($table_ratings as $i => $table_rating) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_rating['customer_id']) {
                    $table_ratings[$i]['customer'] = $customer;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        return $table_ratings;
    }

    public function getReportTime($request)
    {
        $data = [];

        // Total Hours Spent.
        $value = $this->calcTotalHours($request);
        $data['metrics']['total_hours']['value']  = $this->formatHours($value);
        $data['metrics']['total_hours']['change'] = $this->calcChange($value, $this->calcTotalHours($request, true));

        // Avg. Hours Spent per Update
        $value = $this->calcAvgHours($request);
        $data['metrics']['avg_hours']['value']  = $this->formatHours($value);
        $data['metrics']['avg_hours']['change'] = $this->calcChange($value, $this->calcAvgHours($request, true));

        // Tables.
        $data['table_times'] = $this->tableTimes($request);
        $data['table_conv_times'] = $this->tableConvTimes($request);
        $data['table_customer_times'] = $this->tableCustomerTimes($request);

        // Chart.
        $data['chart'] = $this->chartTimes($data['table_times']);

        return $data;
    }

    public function formatHours($value)
    {
        return number_format($value / 3600, 1, '.', ' ');
    }

    public function formatSpentTime($value)
    {
        $dt = \Carbon\Carbon::now();
        $days = $dt->diffInDays($dt->copy()->addSeconds($value));
        $hours = $dt->diffInHours($dt->copy()->addSeconds($value)->subDays($days));
        $minutes = $dt->diffInMinutes($dt->copy()->addSeconds($value)->subDays($days)->subHours($hours));
        return \Carbon\CarbonInterval::days($days)->hours($hours)->minutes($minutes)->forHumans();

        //return number_format($value, 1, '.', ' ');
    }

    /**
     * Total Hours Spent.
     */
    public function calcTotalHours($request, $prev = false)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'timelogs.updated_at');

        return $query->sum('time_spent');
    }

    /**
     * Avg. Hours Spent per Update.
     */
    public function calcAvgHours($request, $prev = false)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select('time_spent')
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            });
        
        $this->applyFilter($query, $request, $prev, 'timelogs.updated_at');

        $timelogs = $query->get();

        if (!count($timelogs)) {
            return 0;
        }

        return ($timelogs->sum('time_spent') / count($timelogs));
    }

    /**
     * Avg. Hours Spent per Update.
     */
    public function calcAvgHoursHandling($request, $prev = false)
    {
        return $this->totalClosedConv($request, $prev)
            ->whereNotNull('closed_at')
            ->avg(\DB::raw('TIMESTAMPDIFF(SECOND, created_at, closed_at)'));
    }

    /**
     * Avg. Hours Spent per Update.
     */
    public function hoursHandlingTime($request)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', Conversation::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->groupBy('closed_by_user_id');
        $this->applyFilter($query, $request);
        $stats = $query->get(array(
            \DB::raw('closed_by_user_id'),
            \DB::raw(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, created_at, closed_at)) as avg_handling_time'))
        ))
            ->sortBy('avg_handling_time')
            ->reverse()
            ->toArray();
        return array_slice($stats, 0, \Reports::MAX_TABLE_ITEMS);
    }

    public function tableTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                // To avoid PostgreSQL error: 
                // column "timelogs.id" must appear in the GROUP BY clause or be used in an aggregate function
                'timelogs.user_id',
                \DB::raw('SUM(timelogs.time_spent) as time_spent'),
                \DB::raw('COUNT(DISTINCT timelogs.id) as time_count'),
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            ->groupBy('timelogs.user_id');
        
        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->toArray();

        $user_ids = array_pluck($table_times, 'user_id');
        $users = User::whereIn('id', $user_ids)->get();
        foreach ($table_times as $i => $table_time) {

            $table_times[$i]['time_spent_h'] = round($table_time['time_spent'] / 3600, 1);
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);
            if (!(int)$table_time['time_count']) {
                $table_time['time_count'] = 1;
            }
            $table_times[$i]['time_avg'] = $this->formatSpentTime($table_time['time_spent'] / $table_time['time_count']);

            foreach ($users as $user) {
                if ($user->id == $table_time['user_id']) {
                    $table_times[$i]['user'] = $user;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        return $table_times;
    }

    public function tableConvTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                // To avoid PostgreSQL error:
                // column "conversations.id" must appear in the GROUP BY clause or be used in an aggregate function
                'timelogs.conversation_id as id',
                \DB::raw('MAX(conversations.subject) as subject'),
                \DB::raw((config('app.custom_number') ? 'MAX(conversations.number)' : 'MAX(conversations.id)').' as number'),
                \DB::raw('SUM(timelogs.time_spent) as time_spent'),
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            //->where('time_spent', '>', 0)
            ->groupBy('timelogs.conversation_id');
        
        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->sortBy('time_spent')->reverse()->toArray();

        $table_times = array_slice($table_times, 0, \Reports::MAX_TABLE_ITEMS);

        foreach ($table_times as $i => $table_time) {
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);
        }

        return $table_times;
    }

    public function tableCustomerTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                'conversations.customer_id',
                \DB::raw('SUM(timelogs.time_spent) as time_spent')
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            ->groupBy('conversations.customer_id');
        
        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->sortBy('time_spent')->reverse()->toArray();

        $table_times = array_slice($table_times, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_times, 'customer_id');
        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_times as $i => $table_time) {
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);

            foreach ($customers as $customer) {
                if ($customer->id == $table_time['customer_id']) {
                    $table_times[$i]['customer'] = $customer;
                    continue 2;
                }
            }
            unset($table_times[$i]);
        }

        return $table_times;
    }

    public function chartTimes($table_times)
    {
        $chart = [];

        $chart['y_title'] = __('Hours');

        $chart['categories'] = [];
        $data = [];

        foreach ($table_times as $table_row) {
            $chart['categories'][] = $table_row['user']->getFullName();
            $data[] = $table_row['time_spent_h'];
        }


        $chart['series'][] = [
            'showInLegend' => false,
            'data'         => $data
        ];

        return $chart;
    }

    public function checkPermissions()
    {
        if (!\Reports::canAccessReports()) {
            \Helper::denyAccess();
        }
    }

    /**
     * Number of closed conversations
     */
    public function countTotalClosedConv($request, $prev = false)
    {
        return $this->totalClosedConv($request, $prev)->count();
    }

    /**
     * Closed conversations
     */
    public function totalClosedConv($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', Conversation::STATUS_CLOSED);

        $this->applyFilter($query, $request, $prev);

        return $query;
    }

    /**
     * Number of closed conversations by AGENT
     */
    public function agentsClosedConv($request, $prev = false)
    {
        $query = $this->totalClosedConv($request, $prev)->groupBy('closed_by_user_id');
//        $this->applyFilter($query, $request, $prev);
        $stats = $query->get(
            array(
                \DB::raw('closed_by_user_id'),
                \DB::raw('COUNT(*) as conversations_count')
            )
        )
            ->sortBy('conversations_count')
            ->reverse()
            ->toArray();
        $test = array_slice($stats, 0, \Reports::MAX_TABLE_ITEMS);

        return $test;
    }

    /**
     * interactions Per Customer DATA
     */
    public function interactionsPerCustomer($request, $prev = false)
    {
        $query = Conversation::select('conversations.customer_id', \DB::raw('CONCAT(COALESCE(customers.first_name, ""), " ", COALESCE(customers.last_name, "")) AS name'), \DB::raw('COUNT(conversations.id) as count'))
            ->leftJoin('customers', 'customers.id', '=', 'conversations.customer_id')
            ->whereNotNull('conversations.created_by_customer_id')
            ->whereNull('conversations.created_by_user_id')
            ->groupBy('conversations.customer_id')
            ->orderBy('count', 'desc');
        $this->applyFilter($query, $request, $prev);

        return $query->get()->toArray();
    }

    /**
     * Resolution times per customer
     */
    public function resolutionTimesPerCustomer($request, $prev = false)
    {
        $query = Conversation::select(
            'customer_id',
            \DB::raw('SUM(TIMESTAMPDIFF(SECOND, created_at, closed_at)) AS seconds'),
            \DB::raw('COUNT(*) AS count')
        )
            ->whereNotNull('customer_id')
            ->whereNotNull('closed_at')
            ->where('status', '!=', 4)
            ->where('state', '=', 2);
        $this->applyFilter($query, $request, $prev);

        return $query->groupBy('customer_id')->get()->toArray();
    }

    /**
     * count Interactions Per Customer
     */
    public function countCustomerInteractionsData($data, $field = null)
    {
        if (!empty($data)) {
            if ($field) {
                return array_sum(array_pluck($data, $field));
            }

            return array_sum($data);
        }

        return 0;
    }

    /**
     * array data for Customer Interaction Report
     */
    public function customerInteractionsReportData($request)
    {
        return [
            'interactions_counts' => [
                'data' => $this->interactionsPerCustomer($request),
                'prev' => $this->interactionsPerCustomer($request, true)
            ],
            'agent_responses_times' => [
                'data' => $this->countFirstResponseTime($request, 'customer_id'),
                'prev' => $this->countFirstResponseTime($request, 'customer_id', true)
            ],
            'customers_response_time' => [
                'data' => $this->countCustomersFirstResponseTime($request, 'customer_id'),
                'prev' => $this->countCustomersFirstResponseTime($request, 'customer_id', true)
            ],
            'resolution_times' => [
                'data' => $this->resolutionTimesPerCustomer($request),
                'prev' => $this->resolutionTimesPerCustomer($request, true)
            ]

        ];
    }

    public function chatAgentActivityGetData($data, $id, $field)
    {
        $result = [];

        foreach ($data as $item) {
            if ($field == 'avg_handling_time') {
                if (!empty((int)$item[$field])) {
                    $result[] = $this->secondsToHMFormat((int)$item[$field]);
                }
            }else {
                $result[] = (int)$item[$field];
            }
        }

        return  $result;
    }

    function secondsToHMFormat($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf("%d.%02d", $hours, $minutes);
    }

    public function countFirstResponseTime($request, $by_field = 'user_id', $prev = false)
    {
        // First message time
        $firstMessageTime = $this->firstMessageTimeData($request, $prev);
//        error_log('FIRST_MESSAGE_TIME: ' . json_encode($firstMessageTime));

        // First response time
        $firstResponseTimes = $this->firstAgentResponseTimeData($request, $prev);
//        error_log('FIRST_RESPONSE_TIME: ' . json_encode($firstResponseTimes));

        $fullAgentTime = [];
        foreach ($firstResponseTimes as $respTimeData) {
            $seconds = 0;
            if (!empty($firstMessageTime[$respTimeData['conversation_id']])) {
                $messageTime = new \DateTime($firstMessageTime[$respTimeData['conversation_id']]['first_message_time']); // First message time
                if (!empty($respTimeData['first_response_time'])) {
                    $respTime    = new \DateTime($respTimeData['first_response_time']); // First response time
                    $interval    = $messageTime->diff($respTime);
                    $seconds     = ($interval->days * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
                }
            }

            // Инициализация элемента массива, если он не существует
            $field = $respTimeData['created_by_user_id'];
            if ($by_field == 'first_response_time') {
                $field = $respTime->format('d M');
            }else if ($by_field == 'customer_id') {
                $field = $firstMessageTime[$respTimeData['conversation_id']]['customer_id'];
            }

            if (!isset($fullAgentTime[$field])) {
                $fullAgentTime[$field]['seconds'] = 0;
                $fullAgentTime[$field]['countResp'] = 0;
            }

            if (!empty((int)$seconds) && (int)$seconds > 0) {
                $fullAgentTime[$field]['seconds'] += (int)$seconds;
                $fullAgentTime[$field]['countResp'] += 1;
            }
        }

        return $fullAgentTime;
    }

    public function countCustomersFirstResponseTime($request, $by_field = 'customer', $prev = false)
    {
        // First agent response time
        $firstAgentRT = $this->firstAgentResponseTimeData($request, $prev);

        // First customers response time
        $firstAgentRTData = [];
        $firstCustomerRT = [];
        if (!empty($firstAgentRT)) {
            foreach ($firstAgentRT as $fARTData) {
                $firstAgentRTData[$fARTData['conversation_id']]['id'] = $fARTData['id'];
                $firstAgentRTData[$fARTData['conversation_id']]['user_id'] = $fARTData['created_by_user_id'];
                $firstAgentRTData[$fARTData['conversation_id']]['first_response_time'] = $fARTData['first_response_time'];

                $query = Thread::select([
                    'threads.id',
                    'threads.conversation_id',
                    'threads.created_by_customer_id as customer',
                    \DB::raw('MIN(threads.created_at) as date')
                ])
                    ->leftJoin('conversations', 'conversations.id', '=', 'threads.conversation_id')
                    ->where(['threads.first' => 0])
                    ->where('threads.conversation_id', $fARTData['conversation_id'])
                    ->where('threads.id', '>', $fARTData['id'])
                    ->whereNull('threads.created_by_user_id')
                    ->whereNotNull('threads.created_by_customer_id')
                    ->whereIn('threads.type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
                    ->groupBy('threads.conversation_id', 'threads.customer_id');
                $this->applyFilter($query, $request, $prev);

                $query = $query->get()->toArray();

                if (!empty($query)) {
                    $firstCustomerRT[] = $query[0];
                }
            }
        }

        $fullCustomerTime = [];
        foreach ($firstCustomerRT as $respTimeData) {
            $seconds = 0;
            if (!empty($firstAgentRTData[$respTimeData['conversation_id']])) {
                $messageTime = new \DateTime($firstAgentRTData[$respTimeData['conversation_id']]['first_response_time']); // First message time
                if (!empty($respTimeData['date'])) {
                    $respTime    = new \DateTime($respTimeData['date']); // First response time
                    $interval    = $messageTime->diff($respTime);
                    $seconds     = ($interval->days * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
                }
            }

            // Инициализация элемента массива, если он не существует
            $field = $respTimeData['customer'];

            if (!isset($fullCustomerTime[$field])) {
                $fullCustomerTime[$field]['seconds'] = 0;
                $fullCustomerTime[$field]['countResp'] = 0;
            }

            if (!empty((int)$seconds) && (int)$seconds > 0) {
                $fullCustomerTime[$field]['seconds'] += (int)$seconds;
                $fullCustomerTime[$field]['countResp'] += 1;
            }
        }

        return $fullCustomerTime;
    }

    public function firstAgentResponseTimeData($request, $prev = false)
    {
        $query = Thread::select([
            'threads.id',
            'threads.conversation_id',
            'threads.created_by_user_id',
            \DB::raw('MIN(threads.created_at) as first_response_time')
        ])
            ->leftJoin('conversations', 'conversations.id', '=', 'threads.conversation_id')
            ->where([
                'threads.first' => 0
            ])
            ->whereNotNull('threads.created_by_user_id')
            ->whereNull('threads.created_by_customer_id')
            ->whereIn('threads.type', [Thread::TYPE_MESSAGE])
            ->whereIn('threads.state', [Thread::STATE_PUBLISHED])
            ->groupBy('threads.conversation_id', 'threads.created_by_user_id');
        $this->applyFilter($query, $request, $prev);

        return $query->get()->toArray();
    }

    public function firstCustomerResponseTimeData($data, $request, $prev = false)
    {

        if (!empty($data)) {

        }
        $query = Thread::select([
            'threads.conversation_id',
            'threads.user_id',
            \DB::raw('MIN(threads.created_at) as first_response_time')
        ])
            ->leftJoin('conversations', 'conversations.id', '=', 'threads.conversation_id')
            ->where([
                'threads.first' => 0
            ])
            ->whereNotNull('threads.user_id')
            ->whereIn('threads.type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
            ->groupBy('threads.conversation_id', 'threads.user_id');
        $this->applyFilter($query, $request, $prev);

        return $query->get()->toArray();
    }

    public function firstMessageTimeData($request, $prev = false)
    {
        $query = Thread::select([
            'threads.conversation_id',
            'threads.customer_id',
            \DB::raw('MIN(threads.created_at) as first_message_time')
        ])
            ->leftJoin('conversations', 'conversations.id', '=', 'threads.conversation_id')
            ->where([
                'threads.first' => 1
            ])
            ->whereIn('threads.type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
            ->groupBy('threads.conversation_id');

        $this->applyFilter($query, $request, $prev);
        $query = $query->get()->toArray();

        if (!empty($query)) {
            $query = array_reduce($query, function ($result, $item) {
                $result[$item['conversation_id']] = [
                    'customer_id' => $item['customer_id'],
                    'first_message_time' => $item['first_message_time']
                ];
                return $result;
            }, []);
        }

        return $query;
    }

    public function tableFirstResponseTimeData($request, $agentData)
    {
        $userIDs = array_keys($agentData);
//        error_log('AGENT_DATA: ' . json_encode($agentData));
//        error_log('AGENTS_IDS: ' . json_encode($userIDs));
        $agents = User::whereIn('id', $userIDs)->select(\DB::raw("CONCAT(first_name, ' ', last_name) as name"), 'id')->pluck('name', 'id')->toArray();
        if (!empty($agents)) {
//            error_log('TABLE_FRTD: ' . json_encode($agents));
            foreach ($agents as $id => $userName) {
                $agentData[$id]['name'] = $userName;
                if (!empty($agentData[$id]['seconds'])) {
                    $agentData[$id]['seconds'] = $this->secondsToTime($agentData[$id]['seconds']);
                }
            }
        }

        return $agentData;

    }

    public function secondsToTime($seconds, $format = false)
    {
        $seconds = (int)$seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($format) {
            switch ($format) {
                case 'hh.mm':
                    return sprintf("%d.%02d", $hours, $minutes, $seconds);
                    break;
            }
        }

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function chartCSATScore($chart, $request)
    {
        $chart['report'] = 'csat_score';
        $data = $this->countCSATScore($request);
        $labels = array_keys($data);

        $scores = array_values($data);

        $chart['labels'] = $labels;
        $chart = $this->chartAddDatasets(
            $chart,
            [
                $scores
            ]
        );
        return $chart;
    }

    public function dataCSATScore($request)
    {
        $query = Thread::select(['threads.rating', 'threads.created_at'])
            ->where('threads.type', Thread::TYPE_MESSAGE)
            ->whereNotNull('threads.rating')
            ->leftJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, false, 'threads.created_at');


        return $query->get()->toArray();
    }

    public function countCSATScore($request)
    {
        $csatScore = $this->dataCSATScore($request);

        $arr = [];
        foreach ($csatScore as $value) {
            $date = (new \DateTime($value['created_at']))->format('d.m.Y');
            if (empty($arr[$date])) {
                $arr[$date]['great'] = 0;
                $arr[$date]['bad'] = 0;
                $arr[$date]['okay'] = 0;
            }
            switch($value['rating']) {
                case \SatRatingsHelper::RATING_GREAT:
                    $arr[$date]['great'] += 1;
                    break;
                case \SatRatingsHelper::RATING_BAD:
                    $arr[$date]['bad'] += 1;
                    break;
                case \SatRatingsHelper::RATING_OKAY:
                    $arr[$date]['okay'] += 1;
                    break;
            }
        }

        $score = [];
        foreach ($arr as $key => $value) {
            $ratings = $value['great'] + $value['bad'] + $value['okay'];
            $satscore = 0;
            if ((int)$ratings) {
                $satscore = ceil(($value['great']*100/$ratings) - ($value['bad']*100/$ratings));
            }

            $score[$key] = $satscore;
        }

        return $score;
    }

    public function customerIdToLabel($customersId)
    {
        $labels = [];
        if (!empty($customersId)) {
            foreach ($customersId as $id) {
                $labels[] = Customer::select(\DB::raw('CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")) as name'))->where('id', $id)->pluck('name')->toArray();
            }
        }

        return $labels;
    }


}