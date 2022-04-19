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
        $results[$name]['number_of_seats'] = $election->getNumberOfSeats();

        // Implicit Ranking
        !$election->getImplicitRankingRule() && $election->setImplicitRanking(false); # Security, default must be true.
        computeResults($election, 'implicitRankingEvaluationOfVotes', $results[$name], $name, $methods);
        $results[$name]['condorcetFormatVotes']['implicitRankingEvaluationOfVotes'] = $election->getVotesListAsString();

        // Explicit Ranking OFF
        $election->setImplicitRanking(false);
        computeResults($election, 'explicitRankingEvaluationOfVotes', $results[$name], $name, $methods);
        $results[$name]['condorcetFormatVotes']['explicitRankingEvaluationOfVotes'] = $election->getVotesListAsString();


    endforeach;

    unset($collection, $election);


    // Export Results

    $create_dir = function (string $dir):void {if (!is_dir($dir)) : mkdir($dir); endif;};

    # Export for each election
    foreach ($results as $name => $election) :
        foreach ($election['methodsResults'] as $methodName => $methodResult) :

            foreach ($methodResult as $mode => $oneResult) :
                if (!$oneResult['active']) : continue; endif;

                echo 'Write Result: '.$name.' - '.$mode.' - '.$methodName."\n";

                $json = json_encode([
                        'Ranking'           => $oneResult['ranking'],
                        'Number Of Seats'   => $election['number_of_seats'],
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
        file_put_contents("$base_dir/implicitRankingEvaluationOfVotes/$name-aggregated-votes-implicit.cvotes", $election['condorcetFormatVotes']['implicitRankingEvaluationOfVotes']);
        file_put_contents("$base_dir/explicitRankingEvaluationOfVotes/$name-aggregated-votes-explicit.cvotes", $election['condorcetFormatVotes']['explicitRankingEvaluationOfVotes']);
    endforeach;

    # Export Pairwise
    foreach ($results as $name => $electionResults) :
            foreach ($electionResults['Pairwise'] as $mode => $pairwise) :
                $path = __DIR__."/../Results_Output/$name/$mode/$name-$mode-Pairwise.json";

                echo "Write Pairwise: $name - $mode\n";
                file_put_contents($path, json_encode($pairwise, \JSON_PRETTY_PRINT));
            endforeach;
    endforeach;

    # Export Condorcet Winner / Loser
    foreach ($results as $name => $electionResults) :
        echo "Write Condorcet Winner/Loser: $name - $mode\n";

        foreach (['explicitRankingEvaluationOfVotes', 'implicitRankingEvaluationOfVotes'] as $mode) :
            $path = __DIR__."/../Results_Output/$name/$mode/$name-$mode-Condorcet.json";

            file_put_contents($path, json_encode(['Condorcet Winner' => $electionResults['CondorcetWinner'][$mode], 'Condorcet Loser' => $electionResults['CondorcetLoser'][$mode]], \JSON_PRETTY_PRINT));
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
                $results['methodsResults'][$method][$index] = [
                    'active'            => true,
                    'ranking'           => $election->getResult($method)->getResultAsString(),
                    'stats'             => $election->getResult($method)->getStats(),
                ];
            } catch (CondorcetPublicApiException $e) {
                echo $e->getMessage();

                $results['methodsResults'][$method][$index] = [
                    'active'            => false,
                    'ranking'           => null,
                    'stats'             => null,
                ];
            }
        endforeach;

        $results['Pairwise'][$index] = $election->getExplicitPairwise();
        $results['CondorcetWinner'][$index] = (string) $election->getCondorcetWinner();
        $results['CondorcetLoser'][$index] = (string) $election->getCondorcetLoser();
    }

    function makeSummary (array $methods, array $results, string $mode): void
    {
        $md = "$mode Summary  \n===========================\n";
        $md .= "This table is not easy to read on the Github preview, can be better with other markdown renderer.   \n---------  \n";
        $md .= "But a **tip**: click of the tab, then use your keyboard arrows to explore it efficiently _(and not your mouse)_.\n---------------------------------------\n";

        $md .= '| --- | Number of Seats | Pairwise | Condorcet Winner | Condorcet Loser |';

        foreach ($methods as $methodName) :
            $md .= " $methodName |";
        endforeach;

        $md .= "\n| --- |";

        for($i=0 ; $i < (count($methods) + 4) ; $i++) :
            $md .= ' --- |';
        endfor;
        $md .= "\n";

        foreach ($results as $name => $electionResults) :

            $md .= "| $name |";

            $md .= ' '.$electionResults['number_of_seats'].' |';

            $md .= " _[Pairwise](Results_Output/$name/$mode/$name-$mode-Pairwise.json)_".' |';

            $md .= ' '.$electionResults['CondorcetWinner'][$mode].' |';
            $md .= ' '.$electionResults['CondorcetLoser'][$mode].' |';

            foreach ($electionResults['methodsResults'] as $methodName => $methodResult) :

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