<?php class AdServer {

    public function shouldAdBeServed(array $publisher_key_values, string $ad_conds) : bool
    {
        //WDS assume that this 'main' $ad_conds is not framed in parentheses
        $ad_conds_char_index = 0;
        $should_ad_be_served = $this->shouldAdBeServedLevelExpression($publisher_key_values, $ad_conds, $ad_conds_char_index);
        if(strlen($ad_conds) === $ad_conds_char_index)
        {
            return $should_ad_be_served;
        }
        else
        {
            throw new UnexpectedValueException("Too many closing parentheses");
        }
    }

    private function shouldAdBeServedLevelExpression(array $publisher_key_values, string $ad_conds, int &$ad_conds_char_index): bool
    {
        $expression = [];
        $ad_conds_len = strlen($ad_conds);
        $ad_conds_parentheses = [];
        $current_token = ''; //emptied when parsing encounters a separator or when a value is used
        $parsed_key = '';
        $parsed_sign = '';
        while($ad_conds_char_index < $ad_conds_len)
        {
            $current_char = $ad_conds[$ad_conds_char_index];
            if($current_char === ' ')
            {
                $ad_conds_char_index = $ad_conds_char_index + 1;
                $current_token = '';
                if($ad_conds[$ad_conds_char_index] === 'o' && $ad_conds[$ad_conds_char_index+1] === 'r')
                {
                    array_push($expression, 'or');
                    $ad_conds_char_index = $ad_conds_char_index + 2;
                    $expression = $this->reduceExpression($expression, $ad_conds, $ad_conds_parentheses, $ad_conds_char_index);
                }
                else if($ad_conds[$ad_conds_char_index] === 'a' && $ad_conds[$ad_conds_char_index+1] === 'n' && $ad_conds[$ad_conds_char_index+2] === 'd')
                {
                    array_push($expression, 'and');
                    $ad_conds_char_index = $ad_conds_char_index + 3;
                    $expression = $this->reduceExpression($expression, $ad_conds, $ad_conds_parentheses, $ad_conds_char_index);
                }
            }
            else if($current_char === '=')
            {
                $parsed_key = $current_token;
                $current_token = '';
                $parsed_sign = '==';
                if(empty($publisher_key_values[$parsed_key]))
                {
                    throw new UnexpectedValueException('Key '.$parsed_key . ' not exists');
                }
                else
                {
                    $publisher_value = $publisher_key_values[$parsed_key];
                }
                if($ad_conds[$ad_conds_char_index+1] === '[')
                {
                    $ad_conds_char_index=$ad_conds_char_index+2;
                    $parsed_sign = '-';
                    while($ad_conds_char_index < strlen($ad_conds))
                    {
                        $current_char = $ad_conds[$ad_conds_char_index];
                        if($current_char === '-')
                        {
                            $left_edge = $current_token;
                            $current_token = '';
                        }
                        else if($current_char === ']')
                        {
                            $right_edge = $current_token;
                            $current_token = '';
                        }
                        else
                        {
                            $current_token = $current_token . $current_char;
                        }
                        if($current_char === ']')
                        {
                            $ad_conds_char_index++;
                            break;
                        }
                        else
                        {
                            $ad_conds_char_index++;
                        }
                    }
                    if($left_edge === '')
                    {
                        throw new UnexpectedValueException('Left range value for key '.$parsed_key.' not specified');
                    }
                    else if($right_edge === '')
                    {
                        throw new UnexpectedValueException('Right range value for key '.$parsed_key.' not specified');
                    }
                    else
                    {
                        $expression_value = $this->calculateLogicalExpression($publisher_value, [$left_edge, $right_edge], $parsed_sign);
                        array_push($expression, $expression_value);
                    }
                }
                else
                {
                    $ad_conds_char_index++;
                    $ad_conds_value = [];
                    while($ad_conds_char_index < strlen($ad_conds))
                    {
                        $current_char = $ad_conds[$ad_conds_char_index];
                        if($current_char === ' ' || $current_char === ')')
                        {
                            if(empty($ad_conds_value))
                            {
                                if($current_token === '')
                                {
                                    throw new UnexpectedValueException('Value for key '.$parsed_key.' missing after =');
                                }
                                $expression_value = $this->calculateLogicalExpression($publisher_value, $current_token, $parsed_sign);
                                $current_token = '';
                                array_push($expression, $expression_value);
                            }
                            else
                            {
                                if($current_token === '')
                                {
                                    throw new UnexpectedValueException('Value for key '.$parsed_key.' missing after comma');
                                }
                                array_push($ad_conds_value, $current_token);
                                $current_token = '';
                                $expression_value = $this->calculateLogicalExpression($publisher_value, $ad_conds_value, $parsed_sign);
                                array_push($expression, $expression_value);
                            }
                            break;
                        }
                        else if($current_char === ',')
                        {
                            $parsed_sign = 'in';
                            array_push($ad_conds_value, $current_token);
                            $current_token = '';
                            $ad_conds_char_index++;
                        }
                        else
                        {
                            $current_token = $current_token . $current_char;
                            $ad_conds_char_index++;
                        }
                    }
                }
            }
            else if($current_char === '<' || $current_char === '>')
            {
                if($ad_conds[$ad_conds_char_index+1] === '=')
                {
                    $parsed_sign = $current_char . $ad_conds[$ad_conds_char_index+1];
                    $ad_conds_char_index = $ad_conds_char_index+2;
                }
                else
                {
                    $parsed_sign = $current_char;
                    $ad_conds_char_index = $ad_conds_char_index+1;
                }
                $parsed_key = $current_token;
                if(empty($publisher_key_values[$parsed_key]))
                {
                    throw new UnexpectedValueException('Key '.$parsed_key.' not exists');
                }
                else
                {
                    $publisher_value = $publisher_key_values[$parsed_key];
                }
                $current_token = '';
                $current_char = $ad_conds[$ad_conds_char_index];
                while($current_char !== ' ' && $current_char !== ')')
                {
                    $current_token = $current_token . $current_char;                    
                    $ad_conds_char_index++;
                    if($ad_conds_char_index >= strlen($ad_conds))
                    {
                        break;
                    }
                    $current_char = $ad_conds[$ad_conds_char_index];
                }
                if($current_token === '')
                {
                    throw new UnexpectedValueException('Value for key '.$parsed_key.' not specified');
                }
                $expression_value = $this->calculateLogicalExpression($publisher_value, $current_token, $parsed_sign);
                array_push($expression, $expression_value);
                $current_token = '';
            }
            else if($current_char === '(')
            {
                array_push($ad_conds_parentheses, '(');
                $ad_conds_char_index++;
                array_push($expression, $this->shouldAdBeServedLevelExpression($publisher_key_values, $ad_conds, $ad_conds_char_index));
            }
            else if($current_char === ')')
            {
                if(count($ad_conds_parentheses) === 0)
                {
                    $expression = $this->reduceExpression($expression, $ad_conds, $ad_conds_parentheses, $ad_conds_char_index, true);
                    if(count($expression) !== 1)
                    {
                        //this should not happen, added only for safety reasons
                        debug_print_backtrace(); //redirect to logger
                        throw new Exception('Internal error, please try later'); 
                    }
                    else
                    {
                        return $expression[0];
                    }
                }
                else
                {
                    array_pop($ad_conds_parentheses);
                    $ad_conds_char_index++;
                }
            }
            else
            {
                $current_token = $current_token . $current_char;
                $ad_conds_char_index++;
            }
        }
        $expression = $this->reduceExpression($expression, $ad_conds, $ad_conds_parentheses, $ad_conds_char_index, true);
        if(count($ad_conds_parentheses) !== 0)
        {
            throw new UnexpectedValueException('Not enough closing brackets');
        }
        if(count($expression) !== 1)
        {
            //this should not happen, added only for safety reasons
            debug_print_backtrace(); //redirect to logger
            throw new Exception('Internal error, please try later');
        }
        else
        {
            return $expression[0];
        }
    }

    //called after add/or added in $expression
    private function reduceExpression(array $expression, string $ad_conds, array &$ad_conds_parentheses, int &$ad_conds_char_index, bool $end = false) : array
    {
        $ad_conds_len = strlen($ad_conds);
        while(count($expression) > 1)
        {
            if($expression[0] === true && $expression[1] === 'or')
            {
                $expression = [true];
                while($ad_conds_char_index < $ad_conds_len)
                {
                    if($ad_conds[$ad_conds_char_index] === '(')
                    {
                        array_push($ad_conds_parentheses, '(');
                    }
                    else if($ad_conds[$ad_conds_char_index] === ')')
                    {
                        if(count($ad_conds_parentheses) > 0)
                        {
                            array_pop($ad_conds_parentheses);
                        }
                        else
                        {
                            return $expression;
                        }
                    }
                    $ad_conds_char_index++;
                }
            }
            else if($expression[0] === true && $expression[1] === 'and' || $expression[0] === false && $expression[1] === 'or')
            {
                array_pop($expression);
                array_pop($expression);
            }
            else if(count($expression) > 2)
            {
                array_pop($expression);
                array_pop($expression);
                $expression[0] = false;
            }
            else
            {
                if($end)
                {
                    //added only to check if there is a reason for $end parameter
                    debug_print_backtrace(); 
                    return [false];
                }
                break;
            }
        }
        return $expression;
    }

    private function calculateLogicalExpression(string $publisher_value, /* string|array min req: PHP 8.0*/ $ad_conds_value, string $parsed_sign) : bool
    {
        switch($parsed_sign){
            case '<':
                return $publisher_value < $ad_conds_value;
            case '>':
                return $publisher_value > $ad_conds_value;
            case '==':
                return $publisher_value == $ad_conds_value;
            case '<=':
                return $publisher_value <= $ad_conds_value;
            case '>=':
                return $publisher_value >= $ad_conds_value;
            case 'in':
                return in_array($publisher_value, $ad_conds_value);
            case '-':
                $left_range = $ad_conds_value[0];
                $right_range = $ad_conds_value[1];
                if(!is_numeric($left_range))
                {
                    throw new UnexpectedValueException('Left range value must be numeric');
                }
                if(!is_numeric($right_range))
                {
                    throw new UnexpectedValueException('Right range value must be numeric');
                }
                return $publisher_value >= $ad_conds_value[0] && $publisher_value <= $ad_conds_value[1];
            default:
                debug_print_backtrace(); //redirect to logger
                throw new Exception('Unknown sing '.$parsed_sign);
        }
    }
}
?>