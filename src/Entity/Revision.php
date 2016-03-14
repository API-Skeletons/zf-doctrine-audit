<?php

namespace ZF\Doctrine\Audit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class Revision
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected $comment;

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(string $value)
    {
        $this->comment = $value;

        return $this;
    }

    protected $timestamp;

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTime $value)
    {
        $this->timestamp = $value;

        return $this;
    }

    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
        return $this;
    }

    private $revisionEntities;

    public function getRevisionEntities(): ArrayCollection
    {
        if (! $this->revisionEntities) {
            $this->revisionEntities = new ArrayCollection();
        }

        return $this->revisionEntities;
    }

    public function __construct()
    {
        $this->setTimestamp(new DateTime());
    }
}
