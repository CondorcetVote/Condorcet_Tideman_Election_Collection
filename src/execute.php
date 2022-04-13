<?php
/*
    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\TidemanCollection;

use CondorcetPHP\Condorcet\Condorcet;
use CondorcetPHP\Condorcet\Election;
use CondorcetPHP\Condorcet\Throwable\CondorcetPublicApiException;
use CondorcetPHP\Condorcet\Tools\TidemanDataCollection;

require_once __DIR__.'/../vendor/autoload.php';

// Memory Limit
ini_set('memory_limit', '4096M');

    // Scan collection directory

    $tideman_collection_list = [];

    $dir = __DIR__.'/../tideman_collection/';
    foreach (\array_diff(\scandir($dir), ['.', '..']) as $fileName) :
        $tideman_collection_list[\str_replace('.HIL', '', $fileName)] = $dir.$fileName;
    endforeach;

    natsort($tideman_collection_list);

    // Compute

    foreach ($tideman_collection_list as $name => $path) :
        echo 'Execute: '.$name."\n";

        $collection = new TidemanDataCollection($path);
        $election = $collection->setDataToAnElection();

        $results[$name] = [];

        // Implicit Ranking
        computeResults($election, 'implicitRankingEvaluationOfVotes', $results[$name], $name);

        // Explicit Ranking OFF
        $election->setImplicitRanking(false);
        computeResults($election, 'explicitRankingEvaluationOfVotes', $results[$name], $name);
    endforeach;

    unset($collection, $election);


    // Export Results
    $create_dir = function (string $dir):void {if (!is_dir($dir)) : mkdir($dir); endif;};

    foreach ($results as $name => $electionResults) :
        foreach ($electionResults as $methodName => $methodResult) :
            foreach ($methodResult as $mode => $oneResult) :
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
                var_dump($path);

                file_put_contents($path, $json);

                // Condorcet Format
                file_put_contents("$base_dir/$name-aggregated_votes.cvotes", $oneResult['votes_aggregated_condorcetFormat']);
            endforeach;
        endforeach;
    endforeach;



    // Functions

    function computeResults (Election $election, string $index, array &$results, string $name)
    {
        foreach (Condorcet::getAuthMethods() as $method) :
            if ($method === 'Instant-runoff') : continue; endif; // Instant Runoff seem to need a fix in rare case

            try {
                echo 'Compute method: '.$name.' - '.$index.' - '.$method."\n";
                $results[$method][$index] = [
                    'ranking' => $election->getResult($method)->getResultAsString(),
                    'stats'   => $election->getResult($method)->getStats(),
                    'number_of_seats'       => $election->getNumberOfSeats(),
                    'votes_aggregated_condorcetFormat' => $election->getVotesListAsString()
                ];
            } catch (CondorcetPublicApiException $e) {
                echo $e->getMessage();
            }
        endforeach;
    }
