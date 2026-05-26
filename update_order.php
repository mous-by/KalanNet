<?php
$dir = new RecursiveDirectoryIterator('app/Http/Controllers');
$ite = new RecursiveIteratorIterator($dir);
foreach($ite as $file) {
    if($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = preg_replace('/->orderBy\(\'nom_eleve\'\)\s*->orderBy\(\'prenom_eleve\'\)/', "->orderBy('prenom_eleve')->orderBy('nom_eleve')", $content);
        if($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
