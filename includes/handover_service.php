<?php
declare(strict_types=1);
/** Hash executable application/test sources; no credentials or data are written to reports. */
function handover_code_hash(): string {
    $root=dirname(__DIR__);$files=[];
    $directory=new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS);
    $filter=new RecursiveCallbackFilterIterator($directory,static function($item){return !$item->isDir() || !in_array($item->getFilename(),['.git','.cursor','.codex','.agents','node_modules','vendor','outputs','scratch','_ai_source','archive'],true);});
    foreach(new RecursiveIteratorIterator($filter) as $file)if($file->isFile() && (in_array(strtolower($file->getExtension()),['php','css','js','cjs','mjs','sql','json'],true) || $file->getFilename()==='.htaccess'))$files[str_replace('\\','/',substr($file->getPathname(),strlen($root)+1))]=hash_file('sha256',$file->getPathname());
    ksort($files);return hash('sha256',json_encode($files,JSON_THROW_ON_ERROR));
}
