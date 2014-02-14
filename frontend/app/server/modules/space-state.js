
request('http://api.compaxion-vm.dev/status.json', function (error, response, body) {
    if (!error && response.statusCode == 200) {
        module.exports = body;
    } else {
        module.exports = null;
    }
})