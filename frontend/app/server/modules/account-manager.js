
var crypto 		= require('crypto');
var moment 		= require('moment');

/* login validation methods */

exports.autoLogin = function(user, pass, callback)
{
    //TODO: Actually allow login!
    callback({user: 'user'});
}

exports.manualLogin = function(user, pass, callback)
{
    //TODO: Actually allow login!
    callback(null, {user: 'user'});
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