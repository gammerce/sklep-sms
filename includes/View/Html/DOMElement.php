<?php
namespace App\View\Html;

class DOMElement implements I_ToHtml
{
    /** @var string */
    protected $name;

    /** @var I_ToHtml[] */
    protected $contents = [];

    /** @var array */
    protected $params = [];

    /**
     * @param I_ToHtml|I_ToHtml[]|string|string[]|null $content
     */
    public function __construct($content = null)
    {
        $items = is_array($content) ? $content : [$content];

        foreach ($items as $item) {
            $this->addContent($item);
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
            if (strlen($value)) {
                $params[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }
        $params = implode(' ', $params);

        $tagName = htmlspecialchars($this->getName());

        $output = "<{$tagName} {$params}>";

        if (!in_array($this->getName(), ['input', 'img', 'br', 'hr'])) {
            foreach ($this->contents as $element) {
                $output .= $element->toHtml();
            }

            $output .= "</{$tagName}>";
        }

        $this->params = $oldParams;

        return $output;
    }

    /**
     * @param I_ToHtml|string $element
     * @return $this
     */
    public function addContent($element)
    {
        if ($element instanceof I_ToHtml) {
            $this->contents[] = $element;
        } elseif ($element !== null) {
            $this->contents[] = new SimpleText($element);
        }

        return $this;
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
    public function getContentsCount()
    {
        return count($this->contents);
    }

    /** @return bool */
    public function isEmpty()
    {
        return $this->getContentsCount() === 0;
    }

    /**
     * @param $key
     * @return string|array
     */
    public function getParam($key)
    {
        return array_get($this->params, $key, '');
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function addClass($value)
    {
        if (empty($this->params['class'])) {
            $this->params['class'] = (string) $value;
        } else {
            $this->params['class'] .= " $value";
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setStyle($key, $value)
    {
        $this->params['style'][$key] = (string) $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
