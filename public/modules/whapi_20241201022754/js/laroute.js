(function () {
    var module_routes = [
    {
        "uri": "mailbox/whapi/simulate",
        "name": "mailboxes.whapi.simulate"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();