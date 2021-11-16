<?php

namespace Depotwarehouse\Jeopardy\Board;

use Depotwarehouse\Jeopardy\Board\Question\FinalJeopardy\State;
use Depotwarehouse\Jeopardy\Board\Question\FinalJeopardyClue;
use Depotwarehouse\Jeopardy\Buzzer\BuzzerStatus;
use Depotwarehouse\Jeopardy\Buzzer\Resolver;
use Depotwarehouse\Jeopardy\Participant\Contestant;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Depotwarehouse\Jeopardy\Participant\ContestantFactory;
use Illuminate\Support\Collection;

class BoardFactory
{

    const JSON_SOURCE = "json";

    protected $filename;
    protected $data_source;
    protected $game_data_path;


    public function __construct($filename, $game_data_path = "game_data/", $data_source = self::JSON_SOURCE)
    {
        $this->filename = $filename;
        $this->game_data_path = $game_data_path;
        $this->data_source = $data_source;
    }

    /**
     * Initializes a board based on the configured data source.
     *
     * @return Board
     * @throws FileNotFoundException
     */
    public function initialize()
    {
        switch ($this->data_source) {
            default:
            case self::JSON_SOURCE:
                $path = $this->game_data_path . $this->filename . ".json";
                if (!file_exists($path)) {
                    $path = $this->game_data_path . "questions.json";
                    if (!file_exists($path)) throw new FileNotFoundException("Could not find the file at path {$path}");
                }

				// Load contestants from discrete file
				$contestantsPath = $this->game_data_path . "contestants.json";
				$contestantsJson = file_get_contents($contestantsPath);
				$json = file_get_contents($path);
				return self::fromJsonWithContestants($json, $contestantsJson);
            break;
        }
    }

    /**
     * @param string $json
     * @return Board
     */
    public function fromJsonWithContestants($json, $contestantsJson)
    {
        $contestantFactory = new ContestantFactory();

        $values = json_decode($json);
        $contestantsValues = json_decode($contestantsJson);
        $contestants = (new Collection($contestantsValues->contestants))->map([ $contestantFactory, 'createFromObject' ]);
	
        $categories1 = mapCategories($values[0]->categories);
        $categories2 = mapCategories($values[1]->categories);

        if (!isset($values[1]->final)) {
            throw new \Exception("Final Jeopardy is not defined in your questions file");
        }

        $finalJeopardyClue = new FinalJeopardyClue($values[1]->final->category, $values[1]->final->clue, $values[1]->final->answer);
        $finalJeopardyState = new State(
			$finalJeopardyClue,
			$contestants->map(function(Contestant $contestant) { return $contestant->getName(); })->toArray()
		);

        $board = new Board(
            $contestants,
            $categories1,
            $categories2,
            new Resolver(),
            new BuzzerStatus(),
            $finalJeopardyState
        );

        return $board;
    }

    /**
     * @param string $json
     * @return Board
     */
    public function fromJson($json)
    {
        $contestantFactory = new ContestantFactory();

        $values = json_decode($json);
        $contestants = (new Collection($values->contestants))->map([ $contestantFactory, 'createFromObject' ]);

        $categories = array_map(
            function (\stdClass $category) {
                return new Category(
                    $category->name,
                    array_map(
                        function (\stdClass $question) {
                            $questionObj = new Question(
                                new Clue($question->clue),
                                new Answer($question->answer),
                                $question->value,
                                (isset($question->daily_double)) ? $question->daily_double : false,
                                (isset($question->type)) ? $question->type : Question::CLUE_TYPE_DEFAULT
                            );
                            if ($questionObj->getClue() == null || (isset($question->used) && $question->used)) {
                                $questionObj->setUsed(true);
                            }
                            return $questionObj;
                        },
                        $category->questions
                    )
                );
            }, $values->categories
        );

        if (!isset($values->final)) {
            throw new \Exception("Final Jeopardy is not defined in your questions file");
        }

        $finalJeopardyClue = new FinalJeopardyClue($values->final->category, $values->final->clue, $values->final->answer);
        $finalJeopardyState = new State(
            $finalJeopardyClue,
            $contestants->map(function(Contestant $contestant) { return $contestant->getName(); })->toArray()
        );

        $board = new Board(
            $contestants,
            $categories,
            new Resolver(),
            new BuzzerStatus(),
            $finalJeopardyState
        );

        return $board;
    }

	private function mapCategories($categories)
	{
        $categoriesMap = array_map(
            function (\stdClass $category) {
                return new Category(
                    $category->name,
                    array_map(
                        function (\stdClass $question) {
                            $questionObj = new Question(
                                new Clue($question->clue),
                                new Answer($question->answer),
                                $question->value,
                                (isset($question->daily_double)) ? $question->daily_double : false,
                                (isset($question->type)) ? $question->type : Question::CLUE_TYPE_DEFAULT
                            );
                            if ($questionObj->getClue() == null || (isset($question->used) && $question->used)) {
                                $questionObj->setUsed(true);
                            }
                            return $questionObj;
                        },
                        $category->questions
                    )
                );
            }, $categories
        );
		
		return $categoriesMap;
	}

}
