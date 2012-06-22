<?
class BoardGenerator{
    public static function generateBoard($id,$genposts=false){
        $board = DataModel::getData('ch_boards',"SELECT * FROM ch_boards WHERE boardID=? LIMIT 1", array($id));
        if(count($board)==0)throw new Exception("No such board.");
        BoardGenerator::generateBoardFromObject($board[0],$genposts);
    }

    public static function generateBoardFromObject($board,$genposts=false,$genthreads=false){
        global $c,$k,$t;
        if(!class_exists("ThreadGenerator"))include(TROOT.'modules/chan/threadgen.php');
        $path = ROOT.DATAPATH.'chan/'.$board->folder.'/';
        $t->loadTheme("chan");
        
        $totalthreads = $c->getData("SELECT COUNT(postID) FROM ch_posts WHERE BID=? AND PID=0 AND options NOT REGEXP ?",array($board->boardID,'d'));
        $totalthreads = $totalthreads[0]['COUNT(postID)'];
        $threads=array(1);
        
        ob_flush;flush();
        ob_start();
        for($i=0;count($threads)>0;$i++){
            if($i>$board->maxPages){
                if(!class_exists("DataGenerator"))include(TROOT.'modules/chan/datagen.php');
                $threads = DataModel::getData('ch_posts',"SELECT postID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT REGEXP ? ORDER BY bumptime DESC LIMIT ".
                        ($i*$c->o['chan_tpp']).",".(($i+1)*$c->o['chan_tpp']),array($board->boardID,'d'));
                $datagen = new DataGenerator();
                for($j=0;$j<count($threads);$j++)$datagen->deletePost($threads[$j]->postID, $board->boardID, false, false);
            }else{
                $threads = DataModel::getData('ch_posts',"SELECT postID,BID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT REGEXP ? AND options REGEXP ? ORDER BY bumptime DESC LIMIT ".
                        ($i*$c->o['chan_tpp']).",".(($i+1)*$c->o['chan_tpp']),array($board->boardID,'d','s'));
                $Uthreads= DataModel::getData('ch_posts',"SELECT postID,BID FROM ch_posts WHERE BID=? AND PID=0 AND options NOT REGEXP ? AND options NOT REGEXP ? ORDER BY bumptime DESC LIMIT ".
                        ($i*$c->o['chan_tpp']).",".(($i+1)*$c->o['chan_tpp']),array($board->boardID,'d','s'));
                $threads=array_merge($threads,$Uthreads);

                ob_clean();
                ?>

                <?='<? define("POST_SHORT",TRUE); ?>'?>
                
                <? require_once(TEMPLATEPATH.'chan_header.php'); ?>
                <?=write_header($board->title.' - '.$c->o['chan_title'],$board,0,$board->options)?>

                <input type="hidden" id="view" value="board" />
                <div class="board">
                    <? for($j=0;$j<count($threads);$j++){
                        if($genposts||$genthreads)ThreadGenerator::generateThread($threads[$j]->postID, $threads[$j]->BID,true);
                        $posts = DataModel::getData('ch_posts',"SELECT postID FROM ch_posts WHERE BID=? AND PID=? AND options NOT REGEXP ? ORDER BY postID DESC LIMIT 3",array($threads[$j]->BID,$threads[$j]->postID,'d'));
                        $postcount = $c->getData("SELECT COUNT(postID) from ch_posts WHERE BID=? AND PID=? AND options NOT REGEXP ?",array($threads[$j]->BID,$threads[$j]->postID,'d'));$postcount=$postcount[0]['COUNT(postID)'];

                        $folder=$board->folder;
                        ?><div class='threadToolbar'>
                            <a href='<?=$k->url("chan",$folder.'/threads/'.$threads[$j]->postID.'.php')?>'>Whole Thread(<?=$postcount?>)</a>
                            <a href='<?=$k->url("chan",$folder.'/threads/'.$threads[$j]->postID.'.php?b=-50')?>'>Last 50</a> 
                            <a href='<?=$k->url("chan",$folder.'/threads/'.$threads[$j]->postID.'.php?e=100')?>'>First 100</a> 
                            <a href='#' class='watchThread' id='<?=$threads[$j]->postID?>'>Watch</a> 
                            <a href='#' class='hideThread' id='<?=$threads[$j]->postID?>'>Hide</a>
                        </div>
                        
                        <?='@ include("'.ROOT.DATAPATH.'chan/'.$folder.'/posts/'.$threads[$j]->postID.'.php");'."\n"?>
                        <div class='thread' id='T<?=$threads[$j]->postID?>' >
                            <a class="omittedText" href="<?=$k->url("chan",$folder.'/threads/'.$threads[$j]->postID.'.php')?>">
                                (<?=$postcount-count($posts)?> posts omitted.
                            </a><br />

                            <? for($n=count($posts)-1;$n>=0;$n--){ ?>
                                <?='@ include("'.ROOT.DATAPATH.'chan/'.$folder.'/posts/'.$posts[$n]->postID.'.php");'."\n"?>
                            <? } ?>
                        </div>
                        <br class='clear' />
                    <? } ?>
                </div>
                <br class="clear" />
                <?=$k->pager(PROOT.$board->folder.'/',$totalthreads,$i,$c->o['chan_tpp'],true)."\n"?>
                 
                <? require_once(TEMPLATEPATH.'chan_footer.php'); ?>
                <?=write_footer($board->title.' - '.$c->o['chan_title'],$board->boardID,$board->folder,0,$board->options)?>

                <?
                file_put_contents($path.$i.'.php',ob_get_contents(),LOCK_EX);
                ob_clean();
            }
        }
        file_put_contents($path.'index.php','<?php include("'.ROOT.DATAPATH.'chan/'.$board->folder.'/0.php"); ?>',LOCK_EX);
    }
}
?>