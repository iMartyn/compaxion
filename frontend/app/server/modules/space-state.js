
var request = require('request');

exports.status = function(newData, callback) {
    request('http://api.compaxion-vm.dev/space/status.json', function (error, response, body) {
        if (!error && response.statusCode == 200) {
            callback(null,JSON.parse(body));
        } else {
            callback(null);
        }
    })
}