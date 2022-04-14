<?php
/*
    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\TidemanCollection;

use CondorcetPHP\Condorcet\Algo\Methods\KemenyYoung\KemenyYoung;
use CondorcetPHP\Condorcet\Condorcet;
use CondorcetPHP\Condorcet\Election;
use CondorcetPHP\Condorcet\Throwable\CondorcetPublicApiException;
use CondorcetPHP\Condorcet\Tools\TidemanDataCollection;

require_once __DIR__.'/../vendor/autoload.php';

// Memory Limit
ini_set('memory_limit', '8192M');


    $isTest = false;

    // Scan collection directory

    $tideman_collection_list = [];

    $dir = __DIR__.'/../Input_Tideman_Election_Collection/';

    $scandir = \array_diff(\scandir($dir), ['.', '..']);
    natsort($scandir);

    $i = 0;
    foreach ($scandir as $fileName) :
        $tideman_collection_list[\str_replace('.HIL', '', $fileName)] = $dir.$fileName;
        if ($isTest && ++$i > 4) : break; endif; # Uncomment for quick dev tests
    endforeach;

    natsort($tideman_collection_list);

    // Compute

    $methods = Condorcet::getAuthMethods();
    natsort($methods);
    !$isTest && KemenyYoung::$MaxCandidates = 9;

    foreach ($tideman_collection_list as $name => $path) :
        echo 'Execute: '.$name."\n";

        $collection = new TidemanDataCollection($path);
        $election = $collection->setDataToAnElection();

        $results[$name] = [];

        // Implicit Ranking
        computeResults($election, 'implicitRankingEvaluationOfVotes', $results[$name], $name, $methods);

        // Explicit Ranking OFF
        $election->setImplicitRanking(false);
        computeResults($election, 'explicitRankingEvaluationOfVotes', $results[$name], $name, $methods);

        $results[$name]['condorcetFormatVotes'] = $election->getVotesListAsString();
    endforeach;

    unset($collection, $election);


    // Export Results

    $create_dir = function (string $dir):void {if (!is_dir($dir)) : mkdir($dir); endif;};

    # Export for each election
    foreach ($results as $name => $electionResults) :
        foreach ($electionResults as $methodName => $methodResult) :
            if ($methodName === 'condorcetFormatVotes') : continue; endif;
            if ($methodName === 'Pairwise') : continue; endif;


            foreach ($methodResult as $mode => $oneResult) :
                if (!$oneResult['active']) : continue; endif;

                echo 'Write Result: '.$name.' - '.$mode.' - '.$methodName."\n";

                $json = json_encode([
                        'Ranking'           => $oneResult['ranking'],
                        'Number Of Seats'   => $oneResult['number_of_seats'],
                        'Stats'             => $oneResult['stats']
                    ],
                    \JSON_PRETTY_PRINT);

                $dir = $base_dir = __DIR__."/../Results_Output/$name";
                $create_dir($dir);

                $dir .= "/$mode";
                $create_dir($dir);

                $path = "$dir/$name-$mode-$methodName.json";

                file_put_contents($path, $json);
            endforeach;
        endforeach;

        // Condorcet Format
        file_put_contents("$base_dir/$name-aggregated_votes.cvotes", $electionResults['condorcetFormatVotes']);
    endforeach;

    # Export Pairwise
    foreach ($results as $name => $electionResults) :
        foreach ($electionResults as $methodName => $methodResult) :
            if ($methodName === 'Pairwise') :
                foreach ($methodResult as $mode => $pairwise) :
                    $path = __DIR__."/../Results_Output/$name/$mode/$name-$mode-Pairwise.json";

                    echo "Write Pairwise: $name - $mode\n";
                    file_put_contents($path, json_encode($pairwise, \JSON_PRETTY_PRINT));
                endforeach;
            endif;
        endforeach;
    endforeach;

    # Make summary
    makeSummary($methods, $results, 'implicitRankingEvaluationOfVotes');
    makeSummary($methods, $results, 'explicitRankingEvaluationOfVotes');

    // Functions

    function computeResults (Election $election, string $index, array &$results, string $name, array $methods)
    {
        foreach ($methods as $method) :
            try {
                echo 'Compute method: '.$name.' - '.$index.' - '.$method."\n";
                $results[$method][$index] = [
                    'active'            => true,
                    'ranking'           => $election->getResult($method)->getResultAsString(),
                    'stats'             => $election->getResult($method)->getStats(),
                    'number_of_seats'    => $election->getNumberOfSeats(),
                ];
            } catch (CondorcetPublicApiException $e) {
                echo $e->getMessage();

                $results[$method][$index] = [
                    'active'            => false,
                    'ranking'           => null,
                    'stats'             => null,
                    'number_of_seats'   => $election->getNumberOfSeats(),
                ];
            }
        endforeach;

        $results['Pairwise'][$index] = $election->getExplicitPairwise();
    }

    function makeSummary (array $methods, array $results, string $mode): void
    {
        $md = '| --- | Pairwise |';

        foreach ($methods as $methodName) :
            $md .= " $methodName |";
        endforeach;

        $md .= "\n| --- |";

        for($i=0 ; $i < (count($methods) + 1) ; $i++) :
            $md .= ' --- |';
        endfor;
        $md .= "\n";

        foreach ($results as $name => $electionResults) :

            $md .= "| $name |";

            $md .= " _[Pairwise](Results_Output/$name/$mode/$name-$mode-Pairwise.json)_".' |';

            foreach ($electionResults as $methodName => $methodResult) :
                if ($methodName === 'condorcetFormatVotes') : continue; endif;
                if ($methodName === 'Pairwise') : continue; endif;

                foreach ($methodResult as $voteMode => $oneResult) :
                    if ($voteMode === $mode) :
                        $md .= ' '.$oneResult['ranking'].' |';
                    endif;
                endforeach;
            endforeach;

            $md .= "\n";
        endforeach;

        file_put_contents(__DIR__."/../$mode-summary.md", $md);
    }