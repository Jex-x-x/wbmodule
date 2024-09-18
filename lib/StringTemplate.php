<?php
namespace Wbs24\Wbapi;

class StringTemplate
{
    public function getInputWithTemplate(string $inputName, array $marks, string $defaultValue, string $account): string
    {
        $inputId = str_replace(['[', ']'], ['-', ''], $inputName);
        $buttonId = $inputId.'_BUTTON';
        $code =
            '<input type="text" size="40" class="input_template '.$account.'wbs24_wbapi_formula_input" name="'.$inputName.'" id="'.$inputId.'" value="'.$defaultValue.'" />'
            .'<button id="'.$buttonId.'">...</button>'
            .'<script>'
                .'if (typeof window.StringTemplate == "undefined") window.StringTemplate = new Wbs24WbapiStringTemplate();'
                .'StringTemplate.setInputHandlers("'.$buttonId.'", "'.$inputId.'", '.\CUtil::PhpToJSObject($marks).');'
            .'</script>'
        ;

        return $code;
    }

    public function getStringByTemplate(string $template, array $markValues, string $defaultValue): string
    {
        $result = $template;
        foreach ($markValues as $mark => $value) {
            $result = str_replace('{'.$mark.'}', $value, $result);
        }
        if (!$result) $result = $defaultValue;

        return $result;
    }
}
