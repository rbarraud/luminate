<? class Ace extends Module{
public static $name="Ace";
public static $author="NexT";
public static $version=1.00;
public static $short='ace';
public static $required=array();
public static $hooks=array("foo");

function displayAceForFile($file){
    if(substr($file,0,7)!='http://')die();
    
    $filetype=substr($file,strrpos($file, ".")+1);
    if($filetype=="js")$filetype="javascript"; //All others match file ending and type already.
    $this->getAceEditor("source",$filetype,file_get_contents($file),"width:100%;height:500px;");
}

function getAceEditor($formname,$mode,$content="",$style=""){
    ?>
    <div name="contents" id="editor" style="<?=$style?>"><?=htmlspecialchars($content)?></div>
    <textarea name="<?=$formname?>" id="editorHolder"style="display:none;"></textarea>
    <div style="<?=$style?>">&nbsp;</div>

    <script src="<?=DATAPATH?>js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="<?=DATAPATH?>js/ace/mode-<?=$mode?>.js" type="text/javascript" charset="utf-8"></script>
    <script>
    var editor;
    $(document).ready(function() {
        if(ace==null)ace = window.__ace_shadowed__;
        editor = ace.edit("editor");
        mode = require("ace/mode/<?=$mode?>").Mode;
        editor.getSession().setMode(new mode());
    });
    function transferToTextarea(){
        $("#editorHolder").html(editor.getSession().getValue());
    }
    </script>
    <?
}

}
?>
