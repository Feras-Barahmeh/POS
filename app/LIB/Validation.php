<?php

namespace APP\LIB;
// TODO: Add Description to validation trait
use APP\Enums\MessageErrorLocation;
use function APP\pr;

trait Validation
{
    private int $_errorsNum = 0;
    private array $_words;
    private bool $flagIfSimpleValidation;
    private array $_regexPatterns = [
        'num'           => '/^[0-9]+(?:\.[0-9]+)?$/',
        'int'           => '/^[0-9]+$/',
        'float'         => '/^[0-9]+\.[0-9]+$/',
        'alpha'         => '/^[a-zA-Z\p{Arabic} ]+$/u',
        'alphaNum'      => '/^[a-zA-Z\p{Arabic}0-9-_ ]+$/u',
        'vDate'         => '/^[1-2][0-9][0-9][0-9]-(?:(?:0[1-9])|(?:1[0-2]))-(?:(?:0[1-9])|(?:(?:1|2)[0-9])|(?:3[0-1]))$/',
        'email'         => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url'           => '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/'
    ];

    public string $messagesLocation ;
    public array $messagesErrors = [];

    public function req($value): bool
    {
        return '' != $value || !empty($value);
    }

    public function num($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['num'], $value);
    }

    public function int($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['int'], $value);
    }

    public function float($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['float'], $value);
    }

    public function alpha($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['alpha'], $value);
    }

    public function alphaNum($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['alphaNum'], $value);
    }
    public function posInt($value): bool
    {
        return $value >= 0 && is_numeric($value);
    }

    public function eq($value, $matchAgainst): bool
    {
        return $value == $matchAgainst;
    }

    public function compare($value, $valueTow): bool
    {
        return $value == $valueTow;
    }

    public function lt($value, $matchAgainst): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) < $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value < $matchAgainst;
        }
        return false;
    }

    public function gt($value, $matchAgainst): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) > $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value > $matchAgainst;
        }
        return false;
    }

    public function min($value, $min): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        } elseif (is_numeric($value)) {
            return $value >= $min;
        }
        return false;
    }

    public function max($value, $max): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value <= $max;
        }
        return false;
    }

    public function between($value, $min, $max): bool
    {
        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        } elseif (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        return false;
    }

    public function floatLike($value, $beforeDP, $afterDP): bool
    {
        if (!$this->float($value)) {
            return false;
        }
        $pattern = '/^[0-9]{' . $beforeDP . '}\.[0-9]{' . $afterDP . '}$/';
        return (bool)preg_match($pattern, $value);
    }

    public function vDate($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['vDate'], $value);
    }

    public function email($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['email'], $value);
    }

    public function url($value): bool
    {
        return (bool)preg_match($this->_regexPatterns['url'], $value);
    }

    public function get($key)
    {
        if(array_key_exists($key, $this->_words)) {
            return $this->_words[$key];
        }
        return false;
    }

    public function feedKey ($key, $data)
    {
        if(array_key_exists($key, $this->_words)) {
            array_unshift($data, $this->_words[$key]);
            return call_user_func_array('sprintf', $data);
        }
        return false;
    }
    private function validationOneArgument(array $partsArgument, $nameAttributeValue, $nameAttribute): void
    {
        $nameMethod = $partsArgument[1][0];
        $valueMin = $partsArgument[2][0];
        if ($this->$nameMethod($nameAttributeValue, $valueMin) === false) {
            $message = $this->feedKey("text_error_" . $nameMethod, [$this->get("table_" . $nameAttribute), $valueMin]);
            if ($this->messagesLocation == MessageErrorLocation::$post) {
                $this->message->addMessage(
                    $message,
                    Messenger::MESSAGE_DANGER
                );
                $this->_errorsNum ++;
            } else {
                $this->messagesErrors[] = $message;
            }
        }
    }
    private function callMinMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(min)\((\d+)\)/", $roleMethod, $minValue)) {
            $this->validationOneArgument($minValue, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function callMaxMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(max)\((\d+)\)/", $roleMethod, $minValue)) {
            $this->validationOneArgument($minValue, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function ltMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(lt)\((\d+)\)/", $roleMethod, $partsArgument)) {
            $this->validationOneArgument($partsArgument, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function gtMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(gt)\((\d+)\)/", $roleMethod, $minValue)) {
            $this->validationOneArgument($minValue, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function eqMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(eq)\((\d+)\)/", $roleMethod, $minValue)) {
            $this->validationOneArgument($minValue, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function compareMethod($roleMethod, $nameAttributeValue, $nameAttribute, $typeInput): void
    {
        if (preg_match_all('/(compare)\(([a-z-A-z]+)\)/', $roleMethod, $m)) {
            $nameMethod     = $m[1][0];
            $paramValue     = $typeInput[$m[2][0]];

            if ($this->$nameMethod($nameAttributeValue, $paramValue) === false) {
                $this->message->addMessage(
                    $this->feedKey("text_error_" . $nameMethod, [
                        $this->get("table_" . $nameAttribute), $this->get("table_" . $m[2][0])
                    ]),
                    Messenger::MESSAGE_DANGER
                );
                $this->_errorsNum ++;
            }
            $this->flagIfSimpleValidation = true;
        }
        else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function validationTowArgument(array $partsArgument, $nameAttributeValue, $nameAttribute): void
    {
        $nameMethod = $partsArgument[1][0];
        $paramOne   = $partsArgument[2][0];
        $paramTow   = $partsArgument[3][0];

        if ($this->$nameMethod($nameAttributeValue, $paramOne, $paramTow) === false) {
            $message = $this->feedKey("text_error_" . $nameMethod, [$this->get("table_" . $nameAttribute), $paramOne, $paramTow]);

            if ($this->messagesLocation == MessageErrorLocation::$post) {
                $this->message->addMessage(
                    $message,
                    Messenger::MESSAGE_DANGER
                );
                $this->_errorsNum ++;
            } else {
                $this->messagesErrors[] = $message;
            }
//            $this->message->addMessage(
//                $message,
//                Messenger::MESSAGE_DANGER
//            );
//            $this->_errorsNum ++;

        }

    }
    private function betweenMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(between)\((\d+),(\d+)\)/", $roleMethod, $partsArgument)) {
            $this->validationTowArgument($partsArgument, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }
    }
    private function floatLikeMethod($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if (preg_match_all("/(floatLike)\((\d+),(\d+)\)/", $roleMethod, $partsArgument)) {
            $this->validationTowArgument($partsArgument, $nameAttributeValue, $nameAttribute);
            $this->flagIfSimpleValidation = true;
        } else {
            $this->flagIfSimpleValidation = false;
        }

    }

    private function isSimpleRole($roleMethod, $nameAttributeValue, $nameAttribute): void
    {
        if($this->$roleMethod($nameAttributeValue) === false) {
            $message = $this->feedKey("text_error_" . $roleMethod, [$this->get("table_" . $nameAttribute)]);
            if ($this->messagesLocation == MessageErrorLocation::$post) {
                $this->message->addMessage (
                    $message,
                    Messenger::MESSAGE_DANGER
                );
                $this->_errorsNum ++;
            } else {
                $this->messagesErrors[] = $message;
            }

        }
    }

    public function isAppropriate($roles, $typeInput, $messagesLocation=null): array|bool
    {
        if ($messagesLocation == MessageErrorLocation::$post) {
            $this->messagesLocation = MessageErrorLocation::$post;
        } else if ($messagesLocation == MessageErrorLocation::$stack) {
            $this->messagesLocation = MessageErrorLocation::$stack;
        }

        $this->_words = $this->language->getDictionary();
        if ($roles) {
            foreach ($roles as $nameAttribute => $rolesFiled) {
                $nameAttributeValue = $typeInput[$nameAttribute];
                foreach ($rolesFiled as $roleMethod ) {
                     $this->callMinMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                     if (! $this->flagIfSimpleValidation)
                        $this->callMaxMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                         $this->ltMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                         $this->gtMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                        $this->eqMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                         $this->betweenMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                        $this->floatLikeMethod($roleMethod, $nameAttributeValue, $nameAttribute);
                    if (! $this->flagIfSimpleValidation)
                        $this->compareMethod($roleMethod, $nameAttributeValue, $nameAttribute, $typeInput);
                    if (! $this->flagIfSimpleValidation)
                        $this->isSimpleRole($roleMethod, $nameAttributeValue, $nameAttribute);
                }
            }

        }

        if ($messagesLocation == MessageErrorLocation::$post || empty($this->messagesErrors))
            return $this->_errorsNum === 0 ;
        else
            return $this->messagesErrors;
    }
}