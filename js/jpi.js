var JPI = {

    actionPath: 'php/action.php',
    debug: false,
    LOG_INFO: 0,
    LOG_ERROR: 1,

    init: function(actionPath) {
        JPI.actionPath = actionPath;
    },

    performAction: function(name, params, callback) {
        JPI.log("Performing action:  " + name);
        var request = new XMLHttpRequest();
        JPI.log("Opening request...");
        request.open('POST', JPI.actionPath, true);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        request.onload = function() {
            JPI.log("Response received (request status:  " + request.status + ")");
            if (request.status >= 200 && request.status < 400) { // Success
                JPI.log("Data received:  " + request.responseText);
                var data = request.responseText;
                callback(JSON.parse(data));
            } else {
                JPI.log("Server-side error", JPI.LOG_ERROR);
                callback({
                    success: false,
                    message: "Server-side error"
                });
            }
        };

        request.onerror = function() {
            JPI.log("Request failure", JPI.LOG_ERROR);
            callback({
                success: false,
                message: "Connection failure"
            });
        };

        var rawParams = "action=" + name;
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                rawParams += "&params[" + key + "]=" + params[key];
            }
        }
        rawParams = encodeURI(rawParams);

        JPI.log("URL parameters:  " + rawParams);
        JPI.log("Sending request...");
        request.send(rawParams);
    },

    log: function(message, logLevel) {
        logLevel = logLevel || JPI.LOG_INFO;
        switch (logLevel) {
            case JPI.LOG_ERROR:
                console.error("JPI Error:  " + message);
                break;
            default:
                if (JPI.debug)
                    console.log("JPI:  " + message);
                break;
        }
    }

};