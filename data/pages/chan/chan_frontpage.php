<? if(!defined("INIT"))die("OH NOES!"); ?>
<? global $l,$t,$c;$t = $l->loadModule('Themes');$t->loadTheme('chan'); ?>
<? global $DYNSTYLE;$DYNSTYLE=PROOT."themes/chan/css/".$c->o['chan_theme']; ?>
<? $t->openPage('Stevenchan'); ?>
    
<h1 id="chanTitle">Stevenchan</h1>
<div id="news">
    <? $boxes = DataModel::getData('','SELECT * FROM ch_frontpage');
    Toolkit::assureArray($boxes);
    foreach($boxes as $box){ ?>
    <div class="box <?=str_replace(',',' ',$box->classes)?>">
        <h2><?=$box->title?></h2><br />
        <?=$l->triggerPARSE('Purplish',$box->text,true,true);?>
        <? $l->triggerHook('frontBox','Purplish',$box); ?>
    </div>
    <? } ?>
    <div class="stretch"></div>
</div>
<div id="postSidebar">
    <h3>Latest Posts:</h3>
    <? $posts = DataModel::getData('','SELECT postID,folder,PID,p.subject,name,trip,file,fileOrig
                                       FROM ch_posts AS p LEFT JOIN ch_boards ON BID=boardID
                                       WHERE p.options NOT REGEXP ? AND p.options NOT REGEXP ?
                                       ORDER BY time DESC LIMIT ?',array('h','d',$c->o['chan_frontposts']));
    $posts = $l->triggerHookSequentially('frontPosts','Purplish',$posts);
    Toolkit::assureArray($posts);
    foreach($posts as $post){
        if($post->PID==0)$post->PID=$post->postID; ?>
        <div class="simplePost">
            <div class="postInfo">
                <a href="<?=PROOT.$post->folder?>">/<?=$post->folder?>/</a> 
                <a href="<?=PROOT.$post->folder.'/threads/'.$post->PID.'.php#'.$post->postID?>"> #<?=$post->postID?></a>
                <span class="postUsername"><?=$post->name?></span>
                <span class="postTripcode"><?=$post->trip?></span>
            </div>
            <article>
                <? if($post->file!=""){ ?>
                    <a class="postImageLink" title="<?=$post->fileOrig?>" href="<?=$c->o['chan_fileloc_extern'].$post->folder.'/files/'.$post->file?>">
                        <img class="postImage" alt="<?=$post->fileOrig?>" src="<?=$c->o['chan_fileloc_extern'].$post->folder.'/thumbs/'.$post->file?>" border="0">
                    </a>
                <? } ?>
                <blockquote>
                    <?=$l->triggerPARSE('Purplish',(strlen($post->subject)>200) ? substr($post->subject,0,200).'...' : $post->subject); ?>
                </blockquote>
            </article>
            <br style="clear:both" />
        </div>
    <? } ?>
</div>

<? $t->closePage(); ?>
