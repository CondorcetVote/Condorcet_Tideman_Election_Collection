<?php
/*
    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\TidemanCollection;

use CondorcetPHP\Condorcet\Algo\Methods\KemenyYoung\KemenyYoung;
use CondorcetPHP\Condorcet\Algo\Methods\STV\CPO_STV;
use CondorcetPHP\Condorcet\Algo\StatsVerbosity;
use CondorcetPHP\Condorcet\Condorcet;
use CondorcetPHP\Condorcet\Election;
use CondorcetPHP\Condorcet\Throwable\CondorcetPublicApiException;
use CondorcetPHP\Condorcet\Tools\Converters\CondorcetElectionFormat;
use CondorcetPHP\Condorcet\Tools\Converters\DavidHillFormat;
use CondorcetPHP\Condorcet\Tools\Converters\DebianFormat;

require_once __DIR__.'/../vendor/autoload.php';

// Memory Limit
ini_set('memory_limit', '12296M');


    $isTest = false;

    // Scan collection directory

    $tideman_collection_list = [];

    $dir = __DIR__.'/../Input_TidemanElectionCollection/';

    $scandir = \array_diff(\scandir($dir), ['.', '..']);
    natsort($scandir);

    $i = 0;
    foreach ($scandir as $fileName) :
        $tideman_collection_list[\str_replace(['.HIL', '.debian_votes', '.cvotes'], ['','',''], $fileName)] = $dir.$fileName;
        if ($isTest && ++$i > 4) : break; endif; # Uncomment for quick dev tests
    endforeach;

    natsort($tideman_collection_list);

    // Compute

    $methods = Condorcet::getAuthMethods();
    natsort($methods);
    !$isTest && (KemenyYoung::$MaxCandidates = 10) && (CPO_STV::$MaxOutcomeComparisons = 100_000);

    foreach ($tideman_collection_list as $name => $path) :
        echo 'Execute: '.$name."\n";

        $collection = match ( (new \SplFileInfo($path))->getExtension() ) {
            'HIL'          => new DavidHillFormat           ($path),
            'debian_votes' => new DebianFormat              ($path),
            'cvotes'       => new CondorcetElectionFormat   ($path),
        };
        $election = $collection->setDataToAnElection();
        $election->setStatsVerbosity(StatsVerbosity::FULL);

        $results[$name] = [];
        $results[$name]['number_of_seats'] = $election->getNumberOfSeats();

        $specifications = "# Specifications: https://github.com/CondorcetPHP/CondorcetElectionFormat\n\n";
        $numberOfVotes = "# This election has ".$election->countVotes()." votes\n";

        // Implicit Ranking
        !$election->getImplicitRankingRule() && $election->setImplicitRanking(false); # Security, default must be true.
        computeResults($election, 'implicitRankingEvaluationOfVotes', $results[$name], $name, $methods);
        $results[$name]['condorcetFormatVotes']['implicitRankingEvaluationOfVotes'] = $specifications . $numberOfVotes;
        $results[$name]['condorcetFormatVotes']['implicitRankingEvaluationOfVotes'] .= CondorcetElectionFormat::exportElectionToCondorcetElectionFormat(election: $election, aggregateVotes: true, includeTags: false, inContext: true);

        // Official conversion to .cvotes
        $results[$name]['condorcetFormatVotes']['officialCvotesConversion'] = $specifications . $numberOfVotes;
        $results[$name]['condorcetFormatVotes']['officialCvotesConversion'] .= CondorcetElectionFormat::exportElectionToCondorcetElectionFormat(election: $election, aggregateVotes: !str_starts_with($name, 'D') ? true : false, includeTags: true, inContext: false);

        // Explicit Ranking
        $election->setImplicitRanking(false);
        computeResults($election, 'explicitRankingEvaluationOfVotes', $results[$name], $name, $methods);
        $results[$name]['condorcetFormatVotes']['explicitRankingEvaluationOfVotes'] = $specifications . $numberOfVotes;
        $results[$name]['condorcetFormatVotes']['explicitRankingEvaluationOfVotes'] .= "# For a better readability of the results, the last rank has been interpreted and added when absent from the original ballot. If you are looking for the most faithful conversion to the original, look at the 'Tideman_Collection_Converted_To_CondorcetElectionFormat' folder.\n";
        $results[$name]['condorcetFormatVotes']['explicitRankingEvaluationOfVotes'] .= CondorcetElectionFormat::exportElectionToCondorcetElectionFormat(election: $election, aggregateVotes: true, includeTags: false, inContext: false);

        writeResults($name, $results[$name]);
    endforeach;

    unset($collection, $election);

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
                echo $e->getMessage()."\n";

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

    // Export Results
    function writeResults (string $name, array &$election): void
    {
        $create_dir = function (string $dir):void {if (!is_dir($dir)) : mkdir($dir); endif;};

        # Export for each election
        foreach ($election['methodsResults'] as $methodName => &$methodResult) :
            foreach ($methodResult as $mode => &$oneResult) :
                if (!$oneResult['active']) : continue; endif;

                echo 'Write Result: '.$name.' - '.$mode.' - '.$methodName."\n";

                if (($methodName === 'Kemeny–Young' && $election['number_of_seats'] > 8) || strlen(\json_encode($oneResult['stats'], \JSON_THROW_ON_ERROR)) > (1048576 * 16)) :
                    if ($methodName === 'Kemeny–Young') :
                        unset($oneResult['stats']['Ranking Scores']);
                    elseif ($methodName === 'CPO STV') :
                        unset($oneResult['stats']['Outcomes']);
                        unset($oneResult['stats']['Condorcet Completion Method Stats']);
                        unset($oneResult['stats']['Outcomes Comparison']);
                    endif;
                endif;

                $json = json_encode([
                        'Ranking'           => $oneResult['ranking'],
                        'Number Of Seats'   => $election['number_of_seats'],
                        'Stats'             => $oneResult['stats']
                    ],
                    \JSON_PRETTY_PRINT|\JSON_UNESCAPED_UNICODE|\JSON_FORCE_OBJECT|\JSON_THROW_ON_ERROR);

                $dir = $base_dir = __DIR__."/../Output_Results/$name";
                $create_dir($dir);

                $dir .= "/$mode";
                $create_dir($dir);

                $path = "$dir/$name-$mode-$methodName.json";

                file_put_contents($path, $json);

                $oneResult['stats'] = null;
            endforeach;
        endforeach;

        # Export Condorcet Format
        file_put_contents("$base_dir/implicitRankingEvaluationOfVotes/$name-aggregated-votes-implicit.cvotes", $election['condorcetFormatVotes']['implicitRankingEvaluationOfVotes']);
        file_put_contents("$base_dir/explicitRankingEvaluationOfVotes/$name-aggregated-votes-explicit.cvotes", $election['condorcetFormatVotes']['explicitRankingEvaluationOfVotes']);
        file_put_contents(__DIR__."/../ConversionToCondorcetElectionFormat/$name.cvotes", $election['condorcetFormatVotes']['officialCvotesConversion']);

        $election['condorcetFormatVotes'] = null;

        # Export Pairwise
        foreach ($election['Pairwise'] as $mode => $pairwise) :
            $path = __DIR__."/../Output_Results/$name/$mode/$name-$mode-Pairwise.json";

            echo "Write Pairwise: $name - $mode\n";
            file_put_contents($path, json_encode($pairwise, \JSON_PRETTY_PRINT|\JSON_UNESCAPED_UNICODE|\JSON_THROW_ON_ERROR));
        endforeach;

        $election['Pairwise'] = null;

        echo "Write Condorcet Winner/Loser: $name - $mode\n";

        # Export Condorcet Winner / Loser
        foreach (['explicitRankingEvaluationOfVotes', 'implicitRankingEvaluationOfVotes'] as $mode) :
            $path = __DIR__."/../Output_Results/$name/$mode/$name-$mode-Condorcet.json";
            file_put_contents($path, json_encode(['Condorcet Winner' => $election['CondorcetWinner'][$mode], 'Condorcet Loser' => $election['CondorcetLoser'][$mode]], \JSON_PRETTY_PRINT|\JSON_UNESCAPED_UNICODE|\JSON_THROW_ON_ERROR));
        endforeach;
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

            $md .= " _[Pairwise](Output_Results/$name/$mode/$name-$mode-Pairwise.json)_".' |';

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