<? global $c,$t,$PAGETITLE,$NO_BUFFER; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <title><?=$PAGETITLE?> - <?=$c->o['sitename']?></title>
    
    <? for($i=0;$i<count($t->css);$i++){
        if(substr($t->css[$i],0,1)=='/')echo("<link rel='stylesheet' type='text/css' href='".DATAPATH."css".$t->css[$i]."' />\n");
        else                            echo("<link rel='stylesheet' type='text/css' href='".THEMEPATH.$t->tname."/".$t->css[$i]."' />\n");
    
    }?>
    <link rel='stylesheet' type='text/css' href='null.css' id='dynstyle' />
    <link rel="icon" type="image/png" href="<?=DATAPATH?>images/favicon.png" />
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" type="text/css" media="all">
    
    <script type="text/javascript">
        //TAKEN FROM: http://stackoverflow.com/questions/1596836/jquery-works-in-firefox-when-firebug-is-running-does-not-work-when-firebug-is-n
        if (typeof console === "undefined") {
            var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
            "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

            window.console = {};
            for (var i = 0; i < names.length; ++i)
                window.console[names[i]] = function() {}
        }
    </script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="<?=DATAPATH?>js/jquery.js"><\/script>')</script>
    <script type="text/javascript" src="<?=DATAPATH?>js/jquery.cookie.js"></script>
    <? include(PAGEPATH.'/meta.php'); ?>
</head>
<? if(BUFFER&&!$NO_BUFFER){ob_flush();flush();} ?>
<body>