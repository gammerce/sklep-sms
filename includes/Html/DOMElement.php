<?php
namespace App\Html;

class DOMElement implements I_ToHtml
{
    /** @var  string */
    protected $name;

    /** @var  I_ToHtml[] */
    protected $contents = [];

    /** @var  array */
    protected $params;

    /**
     * @param I_ToHtml|I_ToHtml[]|string|string[]|null $value
     */
    public function __construct($value = null)
    {
        $contents = is_array($value) ? $value : [$value];

        foreach ($contents as $content) {
            $this->addContent($content);
        }
    }

    public function toHtml()
    {
        $oldParams = $this->params;

        $style = [];
        foreach ((array) $this->getParam('style') as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            $style[] = htmlspecialchars($key) . ': ' . htmlspecialchars($value);
        }
        if (!empty($style)) {
            $this->setParam('style', implode('; ', $style));
        }

        $params = [];
        foreach ($this->params as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            $params[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        $params = implode(' ', $params);

        $output = "<{$this->getName(true)} {$params}>";

        if (!in_array($this->getName(), ['input', 'img', 'br', 'hr'])) {
            foreach ($this->contents as $element) {
                $output .= $element->toHtml();
            }

            $output .= "</{$this->getName(true)}>";
        }

        $this->params = $oldParams;

        return $output;
    }

    /**
     * @param I_ToHtml|string $element
     */
    public function addContent($element)
    {
        if ($element instanceof I_ToHtml) {
            $this->contents[] = $element;
        } elseif ($element !== null) {
            $this->contents[] = new SimpleText($element);
        }
    }

    /**
     * @param string $key
     *
     * @return I_ToHtml
     */
    public function getContent($key)
    {
        return $this->contents[$key];
    }

    /** @return int */
    public function getContentsAmount()
    {
        return count($this->contents);
    }

    /** @return bool */
    public function isEmpty()
    {
        return $this->getContentsAmount() === 0;
    }

    /**
     * @param $key
     * @return string|array
     */
    public function getParam($key)
    {
        return if_isset($this->params[$key], '');
    }

    /**
     * @param string       $key
     * @param string|array $value
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * @param string $value
     */
    public function addClass($value)
    {
        if (empty($this->params['class'])) {
            $this->params['class'] = strval($value);
        } else {
            $this->params['class'] .= " $value";
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setStyle($key, $value)
    {
        $this->params['style'][$key] = strval($value);
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getName($escape = false)
    {
        return $escape ? htmlspecialchars($this->name) : $this->name;
    }

    /** @param string $name */
    public function setName($name)
    {
        $this->name = strval($name);
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
