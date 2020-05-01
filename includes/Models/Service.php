<?php
namespace App\Models;

class Service
{
    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $shortDescription;

    /** @var string */
    private $description;

    /** @var int */
    private $types;

    /** @var string */
    private $tag;

    /** @var string */
    private $module;

    /** @var array */
    private $groups;

    /** @var string */
    private $flags;

    /** @var int */
    private $order;

    /** @var array */
    private $data;

    public function __construct(
        $id,
        $name,
        $shortDescription,
        $description,
        $types,
        $tag,
        $module,
        $groups,
        $flags,
        $order,
        $data
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->shortDescription = $shortDescription;
        $this->description = $description;
        $this->types = $types;
        $this->tag = $tag;
        $this->module = $module;
        $this->groups = $groups;
        $this->flags = $flags;
        $this->order = $order;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNameI18n()
    {
        return __($this->name);
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * @return string
     */
    public function getShortDescriptionI18n()
    {
        return __($this->shortDescription);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDescriptionI18n()
    {
        return __($this->description);
    }

    /**
     * @return int
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return string
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
