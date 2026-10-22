<?php
declare(strict_types=1);

function request_path_is_private(string $path): bool
{
    // Normalize encoded separators before matching so alternate URL spellings are blocked too.
    $path = str_replace('\\', '/', rawurldecode($path));
    if (str_contains($path, "\0") || preg_match('#(?:^|/)\.[^/]*(?:/|$)#', $path)) return true;
    if (preg_match('#^/(?:database|tests|scratch|includes|data|docs|outputs|node_modules|vendor|_ai_source|archive)(?:/|$)#i', $path)) return true;
    return (bool)preg_match('#(?:\.(?:sql|bak|log|ini|toml|md|lock)|/(?:package(?:-lock)?|composer)\.json)$#i', $path);
}
