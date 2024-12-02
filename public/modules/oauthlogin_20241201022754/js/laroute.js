(function () {
    var module_routes = [
    {
        "uri": "oauth-login\/callback\/{provider_id}",
        "name": "oauthlogin.callback"
    },
    {
        "uri": "oauth-login\/logout\/{provider_id}\/{logout_secret}",
        "name": "oauthlogin.logout"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();