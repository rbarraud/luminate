<?
class ThreadGenerator{
    public static function generateThread($id,$board,$posts=false){
        $post = DataModel::getData('ch_posts',"SELECT * FROM ch_posts WHERE postID=? AND BID=? AND PID=0 ORDER BY postID DESC LIMIT 1", array($id,$board));
        if($post==null)throw new Exception("No such post.");
        ThreadGenerator::generateThreadFromObject($post,$posts);
    }

    public static function generateThreadFromObject($post,$posts=false){
        global $c,$k,$t,$l,$PAGETITLE,$METADESCRIPTION,$METAKEYS,$NO_BUFFER;
        $previousTheme = $t->tname;
        $t = $l->loadModule('Themes');
        $t->loadTheme("chan");

        $pID=$post->postID;
        $postlist = $c->getData("SELECT postID FROM ch_posts WHERE PID=? AND BID=? AND `options` NOT LIKE ? ORDER BY postID ASC",array($pID,$post->BID,'%d%'));
        Toolkit::assureArray($postlist);
        if($posts){
            if(!class_exists("PostGenerator"))include('postgen.php');
            PostGenerator::generatePost($pID, $post->BID);
        }
        $board = DataModel::getData('ch_boards',"SELECT boardID,folder,subject,title,filetypes,options FROM ch_boards WHERE boardID=?",array($post->BID));
        $path = ROOT.DATAPATH.'chan/'.$board->folder.'/threads/'.$pID.'.php';

        if(strlen($post->title) > 3){
            $PAGETITLE=$post->title;
        }else if(strlen($post->subject) > 20){
            $PAGETITLE=substr($post->subject, 0, 20).'...';
        }else{
            $PAGETITLE=$post->subject;
        }
        $PAGETITLE=$PAGETITLE.' - '.$board->title;

        if(strlen($post->subject) > 100){
            $METADESCRIPTION=substr($post->subject, 0, 100);
        }else if(strlen($post->subject) > 10){
            $METADESCRIPTION=$post->subject;
        }else{
            $METADESCRIPTION=$PAGETITLE;
        }
        $METAKEYS=$post->title.','.$board->title.','.$board->folder.','.$c->o['chan_title'].','.$c->o['sitename'];

        
        $NO_BUFFER=true;
        ob_start();
        ?>

        <? $temp = array($pID);
        for($i=0;$i<count($postlist);$i++){
            $temp[]=$postlist[$i]['postID'];
            PostGenerator::generatePost($postlist[$i]['postID'], $post->BID);
         } ?>
        <?='<?php $postlist=array("'.implode('","', $temp).'");
        if($_GET["a"]=="postlist")die(implode(";",$postlist));
        if(is_numeric($_GET["a"]))header("Location: '.$k->url("chan",$board->folder).'/posts/".$_GET["a"].".php"); ?>'?>
        
        <? require_once(PAGEPATH.'chan/chan_header.php'); ?>
        <?=write_header($post->title.' - '.$c->o['chan_title'],$board,$pID,$post->options.$board->options,$post->ip);?>

        <div class="threadToolbar">
            <a href="<?=PROOT.$board->folder?>/" title="Return to the board index">Return</a> 
            <a href="?b=-50" title="Show the last 50 posts">Last 50</a> 
            <a href="?e=100" title="Show the first 100 posts">First 100</a>
            <a href='#' class='watchThread' id='<?=$pID?>' title="Add this thread to the watched toolbar">Watch</a>
            <span itemscope="itemscope" itemtype="http://data-vocabulary.org/Breadcrumb" id="breadcrumbs" style="display:none;">
                <a href='<?=PROOT?>' class='chanRoot' itemprop="url">
                    <span itemprop="title"><?=$c->o['chan_title']?></span>
                </a>
                <a href='<?=PROOT.$board->folder?>/' class='boardRoot' itemprop="url">
                    <span itemprop="title"><?=$board->folder?></span>
                </a>
            </span>
        </div>
        
        <?='<? 
        $begin=$_GET["b"];
        if(!is_numeric($begin))$begin='.(-1*$c->o['chan_defaultamount']).';
        if($begin<0)           $begin=count($postlist)+$begin;
        if($begin<=0)          $begin=1;
        $end  =$_GET["e"];
        if(!is_numeric($end)||$end<=0)$end=count($postlist);
        
        @ include("'.ROOT.DATAPATH.'chan/'.$board->folder.'/posts/".$postlist[0].".php");
        
        $n=($begin-1);
        if($begin>1)echo(\'<a href="?b=0&e=\'.$end.\'" class="fetchPrevious" amount="\'.($n+1).\'">Fetch previous 10/\'.$n.\' posts</a>\');
        ?>'?>
        <div id="view" class="thread" id="T<?=$pID?>">
            <?='<?
            for($i=$begin,$temp=count($postlist);$i<$end&&$i<$temp;$i++){
                @ include("'.ROOT.DATAPATH.'chan/'.$board->folder.'/posts/".$postlist[$i].".php");
            } ?>'?>
        </div><br class="clear" />
        <?='<? $n=(count($postlist)-$end);
        if($end<count($postlist))echo(\'<a href="?b=\'.$begin.\'&e=0" class="fetchNext" amount="\'.$n.\'">Fetch next 10/\'.$n.\' posts</a>\');
        ?>'?>
        
        <? require_once(PAGEPATH.'chan/chan_footer.php'); ?>
        <?=write_footer($post->title.' - '.$c->o['chan_title'],$post->BID,$board->folder,$pID,$post->options.$board->options);?>
        
        <?
        file_put_contents($path,ob_get_contents(),LOCK_EX);
        ob_end_clean();
        $NO_BUFFER=false;
        $t->loadTheme($previousTheme);
    }
}
?>
