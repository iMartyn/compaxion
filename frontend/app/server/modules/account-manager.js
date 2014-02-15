
var moment 		= require('moment');
var request = require('request');

/* login validation methods */

exports.autoLogin = function(user, pass, callback)
{
    callback(null);
}

exports.manualLogin = function(user, pass, callback)
{
    request({
        url: 'http://api.compaxion-vm.dev/member/'+user+'/login',
        method: 'POST',
        headers: {Accept: 'application/json'},
        form: {password: pass}
    }, function (error, response, body) {
        if (!error && response.statusCode == 200) {
            callback(null,{user:user, pass:pass});
        } else {
            callback(null);
        }
    })
}

/* record insertion, update & deletion methods */

exports.addNewAccount = function(newData, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}

exports.updateAccount = function(newData, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}

exports.updatePassword = function(email, newPass, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}

/* account lookup methods */

exports.deleteAccount = function(id, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}

exports.getAccountByEmail = function(email, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}

exports.validateResetLink = function(email, passHash, callback)
{
    //TODO: Actually allow login!
    callback(null, {});
}