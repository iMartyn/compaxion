<?php
/**
 * Created by JetBrains PhpStorm.
 * User: martyn
 * Date: 16/09/13
 * Time: 15:56
 * To change this template use File | Settings | File Templates.
 */

class notReallyStaticSpace {

    public static function __callStatic($name,$arguments) {
        var_dump($name);
        var_dump($arguments);
        $_spaceObject = new Space();
        call_user_func_array(array($_spaceObject,$name),$arguments);
    }

/*    public static function statusHTML() {
        echo "This will be dynamic real soon, honest!";
    }*/

}