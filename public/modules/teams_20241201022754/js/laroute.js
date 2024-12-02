(function () {
    var module_routes = [
    {
        "uri": "teams",
        "name": "teams.teams"
    },
    {
        "uri": "teams\/ajax",
        "name": "teams.ajax"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();