<?php
/*
    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);


$dir = __DIR__.'/../Input_Tideman_Election_Collection/';

// Official
for ($i = 1 ; $i <= 99 ; $i++) :
        $filePath = $dir.'A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'.HIL';

        if (!file_exists($filePath)) :
            // Note that A01 and A04 have a formating problem from remote, manually fixed
            $content = file_get_contents('https://rangevoting.org/TiData/A'.$i.'.HIL');

            if ($content !== false) :
                file_put_contents($filePath, $content);
            endif;
        endif;
endfor;

// Debian
for ($i = 1 ; $i <= 6 ; $i++) :

    $n = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    $filePath = $dir.'D'.$n.'.HIL';

    if (!file_exists($filePath)) :
        $content = file_get_contents('https://rangevoting.org/TiData/D'.$n.'.HIL');

        if ($content !== false) :
            file_put_contents($filePath, $content);
        endif;
    endif;
endfor;