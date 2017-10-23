<?php

namespace Src\Bitrix24;

use Carbon\Carbon;

class Task
{
    protected $title;
    protected $responsibleId;
    protected $deadline;
    protected $description;
    protected $checklist;
    protected $accompliceIds = [];
    protected $groupId = null;

    /**
     * Task constructor.
     * @param $title
     * @param $description
     * @param $responsibleId
     * @param array $accompliceIds
     */
    public function __construct($title, $description, $responsibleId = null, $accompliceIds = [])
    {
        $this->title = $title;
        $this->responsibleId = $responsibleId;
        $this->description = $description;
        $this->accompliceIds = $accompliceIds;
    }

    /**
     * @param mixed $title
     * @return Task
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param mixed $responsibleId
     * @return Task
     */
    public function setResponsibleId($responsibleId)
    {
        $this->responsibleId = $responsibleId;

        return $this;
    }

    /**
     * @param mixed $deadline
     * @return Task
     */
    public function setDeadline(Carbon $deadline = null)
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getResponsibleId()
    {
        return $this->responsibleId;
    }

    /**
     * @return Carbon|null
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * @param mixed $description
     * @return Task
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $checklist
     * @return Task
     */
    public function setChecklist($checklist)
    {
        $this->checklist = $checklist;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChecklist()
    {
        return $this->checklist;
    }

    /**
     * @param array $accompliceIds
     * @return Task
     */
    public function setAccompliceIds(array $accompliceIds): Task
    {
        $this->accompliceIds = $accompliceIds;

        return $this;
    }

    /**
     * @return array
     */
    public function getAccompliceIds(): array
    {
        return $this->accompliceIds;
    }

    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return null
     */
    public function getGroupId()
    {
        return $this->groupId;
    }
}