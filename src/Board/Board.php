<?php

namespace Depotwarehouse\Jeopardy\Board;

use Depotwarehouse\Jeopardy\Board\Question\FinalJeopardy;
use Depotwarehouse\Jeopardy\Board\Question\FinalJeopardyClue;
use Depotwarehouse\Jeopardy\Buzzer\BuzzerStatus;
use Depotwarehouse\Jeopardy\Buzzer\Resolver;
use Depotwarehouse\Jeopardy\Participant\Contestant;
use Illuminate\Support\Collection;

class Board
{

    /**
     * A collection of contestants.
     * @var Collection
     */
    protected $contestants;
    /**
     * A collection of Categories.
     * @var Collection
     */
    protected $categories = [ ];
    protected $round;

    /**
     * Our clue for final Jeopardy.
     * @var FinalJeopardy\State
     */
    protected $finalJeopardyState;

    /**
     * The buzzer resolver which resolves who won a particular buzz.
     * @var Resolver
     */
    protected $resolver;

    /**
     * @param Contestant[]|Collection $contestants
     * @param Category[]|Collection $categories
     * @param Resolver $resolver
     * @param BuzzerStatus $buzzerStatus
     * @param FinalJeopardy\State $final
     */
    function __construct($contestants, $categories1, $categories2, Resolver $resolver, BuzzerStatus $buzzerStatus, FinalJeopardy\State $final)
    {
        $this->contestants = ($contestants instanceof Collection) ? $contestants : new Collection($contestants);
        $this->round = 0;
		$this->categories[0] = new Collection($categories1);
        $this->categories[1] = new Collection($categories2);
        $this->resolver = $resolver;
        $this->buzzerStatus = $buzzerStatus;
        $this->finalJeopardyState = $final;
    }

    /**
     * @return BuzzerStatus
     */
    public function getBuzzerStatus()
    {
        return $this->buzzerStatus;
    }

    /**
     * @param BuzzerStatus $buzzerStatus
     * @return $this
     */
    public function setBuzzerStatus(BuzzerStatus $buzzerStatus)
    {
        $this->buzzerStatus = $buzzerStatus;
        return $this;
    }

    /**
     * The current status of the buzzer.
     * @var BuzzerStatus
     */
    protected $buzzerStatus;

    /**
     * @return Resolver
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolves the current buzzer competition and returns the resolution.
     * As a side-effect, this will also disable the buzzer.
     *
     * @return \Depotwarehouse\Jeopardy\Buzzer\BuzzerResolution
     */
    public function resolveBuzzes()
    {
        $resolution = $this->resolver->resolve();
        $this->buzzerStatus->disable();
        return $resolution;
    }

    /**
     * @param Contestant $contestant
     * @param $value
     * @return Contestant
     */
    public function addScore(Contestant $contestant, $value)
    {
        /** @var Contestant $c */
        $c = $this->getContestants()->first(function(Contestant $c) use ($contestant) {
            return $c->getName() == $contestant->getName();
        });

        if ($c == null) {
            //TODO logging.
            echo "Unable to find contestant with name {$contestant->getName()}";
        }

        $c->addScore($value);
        return $contestant;
    }

    /**
     * Gets the first question that matches both the category and value.
     * @param $categoryName
     * @param int $value
     * @return Question
     * @throws QuestionNotFoundException
     */
    public function getQuestionByCategoryAndValue($categoryName, $value)
    {
        //TODO what if we can't find anything? what if either of these return empty. Must throw exceptions, I suppose.

		echo "Category: " . $categoryName . ", value: " . $value . "\n";

        /** @var Category $category */
        $category = $this->categories[$this->round]->first(function (Category $category) use ($categoryName) {
            return $category->getName() == $categoryName;
        });

        if ($category == null) {
            throw new QuestionNotFoundException;
        }

        $question = $category->getQuestions()->first(function (Question $question) use ($value) {
            return $question->getValue() == $value;
        });

        if ($question == null) {
            throw new QuestionNotFoundException;
        }

        return $question;
    }


    /**
     * @return Collection
     */
    public function getContestants()
    {
        return $this->contestants;
    }

    /**
     * @return Collection
     */
    public function getCategories()
    {
        return $this->categories[$this->round];
    }

    public function getFinalJeopardy()
    {
        return $this->finalJeopardyState;
    }

    public function getFinalJeopardyClue()
    {
        return $this->finalJeopardyState->getClue();
    }

	
	public function getRound()
	{
		return $this->round;
	}

	public function setRound($round)
	{
		return $this->round = $round;
	}

	public function toggleRound()
	{
	    return ($this->round == 0) ? $this->round = 1 : $this->round = 0;
	}
}
