<?
class DIVTag extends Tag{
    function parse($content,$args){
        $this->args = array('class'=>array('name'=>'class','type'=>'TEXT','required'=>false,'default'=>''),
                            'style'=>array('name'=>'style','type'=>'TEXT','required'=>false,'default'=>''),
                            'id'   =>array('name'=>'id',   'type'=>'STRI','required'=>false,'default'=>''));
        $args = $this->checkArguments($args);
        if($args===FALSE)return FALSE;
        $this->makeVarsInArgs($args);
        
        $content = '$r.=\'<div class="'.$args['class'][0].'" style="'.$args['style'][0].'" id="'.$args['id'][0].'" >\';'.$content.'$r.=\'</div>\';';
        return $content."\n";
    }
}

class TAGTag extends Tag{
    function parse($content,$args){
        $this->args = array('tag' => array('name'=>'tag',  'type'=>'STRI','required'=>true,'default'=>''),
                            'class'=>array('name'=>'class','type'=>'TEXT','required'=>false,'default'=>''),
                            'style'=>array('name'=>'style','type'=>'TEXT','required'=>false,'default'=>''),
                            'extra'=>array('name'=>'extra','type'=>'TEXT','required'=>false,'default'=>''),
                            'force'=>array('name'=>'force','type'=>'BOOL','required'=>false,'default'=>false));
        $args = $this->checkArguments($args);
        if($args===FALSE)return FALSE;
        $this->makeVarsInArgs($args);
        
        if(trim($content)==''&&$args['force'][0]==false)
            $content='$r.=\'<'.$args['tag'][0].' class="'.$args['class'][0].'" style="'.$args['style'][0].'" '.$args['extra'][0].' />\';';
        else
            $content='$r.=\'<'.$args['tag'][0].' class="'.$args['class'][0].'" style="'.$args['style'][0].'" '.$args['extra'][0].' >\';'.$content.'$r.=\'</'.$args['tag'][0].'>\';';
        return $content;
    }
}

class IFTag extends Tag{
    function parse($content,$args){
        switch(count($args)){
            case 0:return FALSE;break;
            case 1:$args[1]='TRUE';
            case 2:$args[2]='==';
            case 3:break;
            default:return FALSE;break;
        }
        switch($args[2]){
            case '==':
            case '!=':
            case '<=':
            case '>=':
            case '>':
            case '<':
                break;
            case 'like':
                $args[0]='strtolower(trim('.$args[0].'))';
                $args[1]='strtolower(trim('.$args[1].'))';
                $args[2]='==';
                break;
            case '!like':
                $args[0]='strtolower(trim('.$args[0].'))';
                $args[1]='strtolower(trim('.$args[1].'))';
                $args[2]='!=';
                break;
            default:
                return FALSE;
        }
        $args[0] = str_replace(';','&#59;',$args[0]);
        $args[1] = str_replace(';','&#59;',$args[1]);
        $content='if('.$args[0].' '.$args[2].' '.$args[1].'){ '.$content.' }';
        return $content;
    }
}

class LOOPTag extends Tag{
    function parse($content,$args){
        global $k;
        switch(count($args)){
            case 0:return FALSE;break;
            case 1:$args[1]=$args[0];$args[0]=0;
            case 2:$args[2]=1;
            case 3:$args[3]='pos';
            default:
                if(!is_numeric($args[0])&&substr($args[0],0,4)!='$v["'&&substr($args[0],0,5)!='$_g["')return FALSE;
                if(!is_numeric($args[1])&&substr($args[1],0,4)!='$v["'&&substr($args[0],0,5)!='$_g["')return FALSE;
                if(!is_numeric($args[2])&&substr($args[2],0,4)!='$v["'&&substr($args[0],0,5)!='$_g["')return FALSE;
                $args[3]=$k->sanitizeString($args[3]);if($args[3]=='') return FALSE;
                break;
        }
        $content = 'for($v["'.$args[3].'"][0] = '.$args[0].' ;$v["'.$args[3].'"][0]< '.$args[1].' ;$v["'.$args[3].'"][0]+= '.$args[2].' ){ '.$content.' }';
        return $content;
    }
}

class EACHTag extends Tag{
    function parse($content,$args){
        switch(count($args)){
            case 0:return FALSE;break;
            case 1:$args[1]='item';
            case 2:$args[2]='pos';
        }
        if(substr($args[0],0,1)=='*')$p='$_g';else $p='$v';
        $content = 'foreach('.$p.'["'.$args[0].'"] as $v["'.$args[2].'"][0] => $v["'.$args[1].'"][0]){'.$content.'}';
        return $content;
    }
}

class SETTag extends Tag{
    function parse($content,$args){
        global $k;
        if($args[1]=='')$args[1]=0;
        if($k->sanitizeString($args[0],'*-_')!=$args[0])return FALSE;
        if(substr($args[0],0,1)=='*')$p='$_g';else $p='$v';
        return $p.'["'.$args[0].'"]['.$args[1].']='.str_replace(';','&#59;',$content).';';
    }
}

class GETTag extends Tag{
    function parse($content,$args){
        global $k;
        if(trim($content)=='')return FALSE;
        if($args[0]=='')$args[0]=0;
        if(substr($content,0,1)=='*')$p='$_g';else $p='$v';
        if($k->sanitizeString($content,'*-_')!=$content)return $p.'['.str_replace(';','&#59;',$content).']['.$args[0].']';
        return $p.'["'.$content.'"]['.$args[0].']';
    }
}

class PRINTTag extends Tag{
    function parse($content,$args){
        global $k;
        if(trim($content)=='')return FALSE;
        if($args[0]=='')$args[0]=0;
        if(substr($content,0,1)=='*')$p='$_g';else $p='$v';
        if($k->sanitizeString($content,'*-_')!=$content)return '$r.= '.str_replace(';','&#59;',$content).';';
        else return '$r.= '.$p.'["'.$content.'"]['.$args[0].'];';
    }
}

class ECHOTag extends Tag{
    function parse($content,$args){
        if(trim($content)=='')return FALSE;
        $content = $this->makeVarsInString($content);
        return '$r.=\''.str_replace(';','&#59;',$content).'\';';
    }
}

class REPLACETag extends Tag{
    function parse($content,$args){
        if(trim($content)=='')return FALSE;
        $this->makeVarsInArgs($args);
        $content = $this->makeVarsInString($content);
        return 'str_replace("'.$args[0].'",\''.$args[1].'\',\''.$content.'\')';
    }
}

class REGEXTag extends Tag{
    function parse($content,$args){
        $this->args = array('search'=> array('name'=>'search',  'type'=>'TEXT','required'=>true,'default'=>''),
                            'replace'=>array('name'=>'replace', 'type'=>'TEXT','required'=>true,'default'=>''),
                            'options'=>array('name'=>'options', 'type'=>'STRI','required'=>false,'default'=>'is'),
                            'limit'=>  array('name'=>'limit',   'type'=>'INTE','required'=>false,'default'=>-1),
                            'counter'=>array('name'=>'counter', 'type'=>'STRI','required'=>false,'default'=>'count'));
        $args = $this->checkArguments($args);
        if($args===FALSE)return FALSE;
        $this->makeVarsInArgs($args);
        $content = $this->makeVarsInString($content);
        
        return 'preg_replace(\'`'.$args['search'][0].'`'.$args['options'][0].'\',\''.$args['replace'][0].'\','.$content.','.$args['limit'][0].',$v[\''.$args['counter'].'\'][0])';
    }
}

class MATHTag extends Tag{
    function parse($content,$args){
        global $k;
        foreach($args as $key => $val)$args[$key] = str_replace(';','&#59;',$val);
        switch($content){
            case 'add':
            case '+':
                $final = '';
                foreach($args as $arg)$final.=$arg.'+';
                return substr($final,0,strlen($final)-1);
                break;
            case 'sub':
            case 'subtract':
            case '-':
                $final = '';
                foreach($args as $arg)$final.=$arg.'-';
                return substr($final,0,strlen($final)-1);
                break;
            case 'mult':
            case 'multiply':
            case '*':
                $final = '';
                foreach($args as $arg)$final.=$arg.'*';
                return substr($final,0,strlen($final)-1);
                break;
            case 'div':
            case 'divide':
            case '/':
                $final = '';
                foreach($args as $arg)$final.=$arg.'/';
                return substr($final,0,strlen($final)-1);
                break;
            case 'pow':
            case 'power':
            case '^':
                return 'pow('.$args[0].','.$args[1].')';
                break;
            default: return FALSE;
        }
    }
}

class URLARGSTAG extends Tag{
    function parse($content,$args){
        $this->args = array('var'=> array('name'=>'var',  'type'=>'STRI','required'=>false,'default'=>'params'));
        $args = $this->checkArguments($args);
        $this->makeVarsInArgs($args);
        $content = $this->makeVarsInString($content);
        
        return 'parse_str( parse_url( \''.$content.'\' , PHP_URL_QUERY ), $v["'.$args['var'][0].'"] );';
    }
}

class BAILOUTTag extends Tag{
    function parse($content,$args){
        return 'return FALSE;';
    }
}

class VALIDATETag extends Tag{
    function parse($content,$args){
        $this->args = array('0'=>array('name'=>'0','type'=>$content,'required'=>true,'default'=>''));
        $args = $this->checkArguments($args);
        if($args===FALSE)return 'FALSE';
        else             return 'TRUE';
    }
}

class AUTHTag extends Tag{
    function parse($content,$args){
        $this->args = array('perm'=> array('name'=>'perm',  'type'=>'TEXT','required'=>false,'default'=>'base.*'));
        $args = $this->checkArguments($args);
        $this->makeVarsInArgs($args);
        $content = $this->makeVarsInString($content);
        
        return '$a->check(\''.$args['perm'][0].'\');';
    }
}

class JAVASCRIPTTag extends Tag{
    function parse($content,$args){
        $content = $this->makeVarsInString($content);
        return '$r.=\'<script type="text/javascript">'.str_replace('&dollar;','$',$content).'</script>\';';
    }
}
?>