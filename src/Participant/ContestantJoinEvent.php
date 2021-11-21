<?php

namespace Depotwarehouse\Jeopardy\Participant;

use League\Event\AbstractEvent;

class ContestantJoinEvent extends AbstractEvent
{

    protected $contestant;

    public function __construct($contestant)
    {
        $this->contestant = $contestant;
    }

    /**
     * @return string
     */
    public function getContestant()
    {
        return $this->contestant;
    }

}
