<?php

namespace croacworks\essentials\validators;

use Yii;
use yii\validators\Validator;

class CpfCnpjValidator extends Validator
{

    public function init()
    {
        parent::init();
        $this->message = Yii::t('app', 'CPF inválido.');
    }

    public function validateAttribute($model, $attribute)
    {   //48.108.065/0001-81

        $cpf_cnpj = $this->sanatizeNoReplace($model->$attribute);

        if (strlen($cpf_cnpj) == 11 && !$this->validaCPF($cpf_cnpj)) {
            $this->addError($model, $attribute, Yii::t('app', 'CPF inválido.'));
            return false;
        } else if (strlen($cpf_cnpj) == 14 && !$this->validaCNPJ($cpf_cnpj)) {
            $this->addError($model, $attribute, Yii::t('app', 'CNPJ inválido.'));
            return false;
        } else if ((strlen($cpf_cnpj) > 11 && strlen($cpf_cnpj) < 14) || strlen($cpf_cnpj) > 14) {
            $this->addError($model, $attribute, Yii::t('app', 'CPF/CNPJ inválido.'));
        }
        return true;
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        $message = json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<< JS
            // Valida CPF
            function validaCPF(cpf) {  
                cpf = cpf.replace(/[^\d]+/g,'');    
                if(cpf == '') return false;   
                if (
                    cpf.length != 11 || 
                    cpf == "00000000000" || 
                    cpf == "11111111111" || 
                    cpf == "22222222222" || 
                    cpf == "33333333333" || 
                    cpf == "44444444444" || 
                    cpf == "55555555555" || 
                    cpf == "66666666666" || 
                    cpf == "77777777777" || 
                    cpf == "88888888888" || 
                    cpf == "99999999999" || 
                    cpf == "01234567890" )
                    return false;      
                add = 0;    
                for (i=0; i < 9; i ++)       
                add += parseInt(cpf.charAt(i)) * (10 - i);  
                rev = 11 - (add % 11);  
                if (rev == 10 || rev == 11)     
                    rev = 0;    
                if (rev != parseInt(cpf.charAt(9)))     
                    return false;    
                add = 0;    
                for (i = 0; i < 10; i ++)        
                    add += parseInt(cpf.charAt(i)) * (11 - i);  
                rev = 11 - (add % 11);  
                if (rev == 10 || rev == 11) 
                    rev = 0;    
                if (rev != parseInt(cpf.charAt(10)))
                    return false;       
                return true;   
            }

            // Valida CNPJ
            function validaCNPJ(CNPJ) {
                CNPJ = CNPJ.replace(/[^\d]+/g,''); 
                var a = new Array();
                var b = new Number;
                var c = [6,5,4,3,2,9,8,7,6,5,4,3,2];
                for (i=0; i<12; i++){
                    a[i] = CNPJ.charAt(i);
                    b += a[i] * c[i+1];
                }
                if ((x = b % 11) < 2) { a[12] = 0 } else { a[12] = 11-x }
                b = 0;
                for (y=0; y<13; y++) {
                    b += (a[y] * c[y]);
                }
                if ((x = b % 11) < 2) { a[13] = 0; } else { a[13] = 11-x; }
                if ((CNPJ.charAt(12) != a[12]) || (CNPJ.charAt(13) != a[13])){
                    return false;
                }
                if (CNPJ == 00000000000000) {
                    return false;
                }
                return true;
            }

            function validar(value) { 
                if(value.replace(/[^\d]+/g,'').length == 11){
                    return validaCPF(value);
                }else if(value.replace(/[^\d]+/g,'').length == 14){
                    return validaCNPJ(value);
                }
                return false;
            }

            if(!validar(value)) {
                messages.push({$message});
            }
        JS;
    }

    public function sanatizeNoReplace($str) {
        $removeItens = ["[","]",",","(",")",";",":","|","!","\"","$","%","&","#","=","?","~",">","<","ª","º","-",".","\/"];
        foreach ($removeItens as $item){
            $str = preg_replace('/['.$item.']/', '', $str);            
        }
        return $str;
    } 

    static function sanatizeReplace($str)
    {
        $removeItens = ["[", "]", ",", "(", ")", ";", ":", "|", "!", "\"", "$", "%", "&", "#", "=", "?", "~", ">", "<", "ª", "º", "-", ".", "\/", " "];
        foreach ($removeItens as $item) {
            $str = preg_replace('/[' . $item . ']/', '_', $str);
        }
        return $str;
    }

    static function validaCPF($cpf)
    {

        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    static function validaCNPJ($cnpj)
    { {
            $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

            // Valida tamanho
            if (strlen($cnpj) != 14)
                return false;

            // Verifica se todos os digitos são iguais
            if (preg_match('/(\d)\1{13}/', $cnpj))
                return false;

            // Valida primeiro dígito verificador
            for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
                $soma += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }

            $resto = $soma % 11;

            if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
                return false;

            // Valida segundo dígito verificador
            for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
                $soma += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }

            $resto = $soma % 11;

            return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
        }
    }
}
