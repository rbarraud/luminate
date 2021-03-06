<?
class BoardGenerator{
    public static function generateBoard($id,$genposts=false){
        $board = DataModel::getData('ch_boards',"SELECT * FROM ch_boards WHERE boardID=? LIMIT 1", array($id));
        if($board==null)throw new Exception("No such board.");
        BoardGenerator::generateBoardFromObject($board,$genposts);
    }

    public static function generateBoardFromObject($board,$genposts=false,$genthreads=false){
        global $c,$k,$t,$l,$PAGETITLE,$METADESCRIPTION,$METAKEYS,$NO_BUFFER;
        if(!class_exists("ThreadGenerator"))include('threadgen.php');
        $path = ROOT.DATAPATH.'chan/'.$board->folder.'/';
        $previousTheme = $t->tname;
        $t = $l->loadModule('Themes');
        $t->loadTheme("chan");

        $stickies= DataModel::getData('ch_posts',"SELECT postID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT LIKE ? AND options LIKE ? ORDER BY bumptime DESC",
                                                    array($board->boardID,'%d%','%s%'));
        Toolkit::assureArray($stickies);
        $threads = DataModel::getData('ch_posts','SELECT postID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT LIKE ? AND options NOT LIKE ? ORDER BY bumptime DESC LIMIT ?,?',
                                                    array($board->boardID,'%d%','%s%',0,($c->o['chan_tpp']*$board->maxpages)-count($stickies)));
        Toolkit::assureArray($threads);
        $threads = array_merge($stickies, $threads);

        $PAGETITLE=$board->title.' - '.$c->o['chan_title'];
        $METADESCRIPTION=$PAGETITLE;
        $METAKEYS=$board->title.','.$board->folder.','.$c->o['chan_title'].','.$c->o['sitename'];
        
        $NO_BUFFER=true; //To stop the theme header to flush automatically
        ob_start();

        //Build board pages
        $totalthreads = count($threads);
        for($i=0;$i<$totalthreads;$i+=$c->o['chan_tpp']){
            ?>
            <?='<? define("POST_SHORT",TRUE); ?>'?>
            <? require_once(PAGEPATH.'chan/chan_header.php'); ?>
            <?=write_header($board->title.' - '.$c->o['chan_title'],$board,0,$board->options)?>

            <div id="view" class="board">
                <? for($j=$i;$j<$i+$c->o['chan_tpp'] && $j<$totalthreads;$j++){
                    if($genposts||$genthreads)ThreadGenerator::generateThread($threads[$j]->postID, $board->boardID,true);
                    $posts = DataModel::getData('ch_posts',"SELECT postID FROM ch_posts WHERE BID=? AND PID=? AND options NOT LIKE ? ORDER BY postID DESC LIMIT 3",
                                                            array($board->boardID,$threads[$j]->postID,'%d%'));
                    $postcount = $c->getData("SELECT COUNT(postID) from ch_posts WHERE BID=? AND PID=? AND options NOT LIKE ?",
                                                            array($board->boardID,$threads[$j]->postID,'%d%'));
                    $postcount=$postcount[0]['COUNT(postID)'];
                    Toolkit::assureArray($posts);
                    
                    $folder=$board->folder;
                    ?><div class='threadToolbar'>
                        <a href='<?=PROOT.$folder.'/threads/'.$threads[$j]->postID.'.php?b=1'?>'>Whole Thread(<?=$postcount?>)</a>
                        <a href='<?=PROOT.$folder.'/threads/'.$threads[$j]->postID.'.php?b=-50'?>'>Last 50</a> 
                        <a href='<?=PROOT.$folder.'/threads/'.$threads[$j]->postID.'.php?e=100'?>'>First 100</a> 
                        <a href='#' class='watchThread' id='<?=$threads[$j]->postID?>'>Watch</a> 
                        <a href='#' class='hideThread'  id='<?=$threads[$j]->postID?>'>Hide</a>
                    </div>
                    
                    <?='<? @ include("'.ROOT.DATAPATH.'chan/'.$folder.'/posts/'.$threads[$j]->postID.'.php"); ?>'?>
                    <a class="omittedText" href="<?=PROOT.$folder.'/threads/'.$threads[$j]->postID.'.php'?>">
                        (<?=$postcount-count($posts)?> posts omitted.)
                    </a>
                    <div class='thread' id='T<?=$threads[$j]->postID?>' >
                        <?='<?'?>
                        <? for($n=count($posts)-1;$n>=0;$n--){ ?>
                            <?=' @ include("'.ROOT.DATAPATH.'chan/'.$folder.'/posts/'.$posts[$n]->postID.'.php");'."\n"?>
                        <? } ?>
                        <?='?>'?>
                    </div>
                    <br class='clear' />
                <? } ?>
            </div>
            <br class="clear" />
            <?=$k->pager(PROOT.$board->folder.'/',$totalthreads,$i/$c->o['chan_tpp'],$c->o['chan_tpp'],true)."\n"?>
            <? require_once(PAGEPATH.'chan/chan_footer.php'); ?>
            <?=write_footer($board->title.' - '.$c->o['chan_title'],$board->boardID,$board->folder,0,$board->options);?>

            <?                    
            file_put_contents($path.($i/$c->o['chan_tpp']).'.php',ob_get_contents(),LOCK_EX);
            ob_clean();
        }

        file_put_contents($path.'index.php','<?php include("'.ROOT.DATAPATH.'chan/'.$board->folder.'/0.php"); ?>',LOCK_EX);
        ob_end_clean();
        $NO_BUFFER=false;
        $t->loadTheme($previousTheme);

        //Delete posts.
        $toDelete = DataModel::getData("ch_posts","SELECT postID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT LIKE ? AND options NOT LIKE ? ORDER BY bumptime DESC LIMIT ?,?",
                                                    array($board->boardID,'%d%','%s%',$totalthreads,18446744073709551615));
        Toolkit::assureArray($toDelete);
        $datagen = new DataGenerator();
        foreach($toDelete as $thread){
            $datagen->deletePost($thread->postID, $board->boardID, false, false, true);
        }

        //That's it.
    }
}
?>
