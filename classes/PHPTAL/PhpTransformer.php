<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  


/**
 * Tranform php: expressions into their php equivalent.
 *
 * This transformer produce php code for expressions like :
 *
 * - a.b["key"].c().someVar[10].foo()
 * - (a or b) and (c or d)
 * - not myBool
 * - ...
 *
 * The $prefix variable may be changed to change the context lookup.
 *
 * example:
 * 
 *      $res = PHPTAL_PhpTransformer::transform('a.b.c[x]', '$ctx->');
 *      $res == '$ctx->a->b->c[$ctx->x]';
 *
 * A brave coder may decide to cleanup the parser, optimize things, and send 
 * me a patch :) He will be welcome. 
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_PhpTransformer
{
    const ST_NONE = 0;
    const ST_STR  = 1;    // 'foo' 
    const ST_ESTR = 2;    // "foo ${x} bar"
    const ST_VAR  = 3;    // abcd
    const ST_NUM  = 4;    // 123.02
    const ST_EVAL = 5;    // ${somevar}
    const ST_MEMBER = 6;  // abcd.x
    const ST_STATIC = 7;  // class::[$]static|const
    const ST_DEFINE = 8;  // @MY_DEFINE

    public static function transform( $str, $prefix='$' )
    {
        $state = self::ST_NONE;
        $result = "";
        $i = 0;
        $len = strlen($str);
        $inString = false;
        $backslashed = false;
        $instanceOf = false;
        $eval = false;

        for ($i = 0; $i <= $len; $i++) {
            if ($i == $len) $c = "\0";
            else $c = $str[$i];

            switch ($state) {
                case self::ST_NONE:
                    if (self::isAlpha($c)) {
                        $state = self::ST_VAR;
                        $mark = $i;
                    }
                    else if ($c == '"') {
                        $state = self::ST_ESTR;
                        $mark = $i;
                        $inString = true;
                    }
                    else if ($c == '\'') {
                        $state = self::ST_STR;
                        $mark = $i;
                        $inString = true;
                    }
                    else if ($c == ')' || $c == ']' || $c == '}') {
                        $result .= $c;
                        if ($i < $len-1 && $str[$i+1] == '.') {
                            $result .= '->';
                            $state = self::ST_MEMBER;
                            $mark = $i+2;
                            $i+=2;
                        }
                    }
                    else if ($c == '@') { // defines, ignore char
                        $state = self::ST_DEFINE;
                        $mark = $i+1;
                    }
                    else {
                        $result .= $c;
                    }
                    break;
                
                case self::ST_STR:
                    if ($c == '\\') {
                        $backslashed = true;
                    }
                    else if ($backslashed) {
                        $backslashed = false;
                    }
                    else if ($c == '\'') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $inString = false;
                        $state = self::ST_NONE;
                    }
                    break;

                case self::ST_ESTR:
                    if ($c == '\\') {
                        $backslashed = true;
                    }
                    else if ($backslashed) {
                        $backslashed = false;
                    }
                    else if ($c == '"') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $inString = false;
                        $state = self::ST_NONE;
                    }
                    else if ($c == '$' && $i < $len && $str[$i+1] == '{') {
                        $result .= substr( $str, $mark, $i-$mark ) . '{';
                        
                        $sub = 0;
                        for ($j = $i; $j<$len; $j++) {
                            if ($str[$j] == '{') {
                                $sub++;
                            }
                            elseif ($str[$j] == '}' && (--$sub) == 0) {
                                $part = substr( $str, $i+2, $j-$i-2 );
                                $result .= self::transform($part, $prefix);
                                $i = $j;
                                $mark = $i;
                            }
                        }
                    }
                    break;

                case self::ST_VAR:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '.') {
                        $result .= $prefix . substr( $str, $mark, $i-$mark );
                        $result .= '->';
                        $state = self::ST_MEMBER;
                        $mark = $i+1;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $mark = $i+1;
                        $i++;
                        $state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $result .= $prefix . substr( $str, $mark, $i-$mark+1 );
                        $state = self::ST_NONE;
                    }
                    else {
                        $var = substr( $str, $mark, $i-$mark );
                        $low = strtolower($var);
                        // boolean and null
                        if ($low == 'true' || $low == 'false' || $low == 'null') {
                            $result .= $var;
                        }
                        // lt, gt, ge, eq, ...
                        else if (array_key_exists($low, self::$TranslationTable)){
                            $result .= self::$TranslationTable[$low];
                        }
                        // instanceof keyword
                        else if ($low == 'instanceof'){
                            $result .= $var;
                            $instanceOf = true;
                        }
                        // previous was instanceof
                        else if ($instanceOf){
                            // last was instanceof, this var is a class name
                            $result .= $var;
                            $instanceOf = false;
                        }
                        // regular variable
                        else {
                            $result .= $prefix . $var;
                        }
                        $i--;
                        $state = self::ST_NONE;
                    }
                    break;

                case self::ST_MEMBER:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '$') {
                        $result .= '{' . $prefix;
                        $mark++;
                        $eval = true;
                    }
                    else if ($c == '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        if ($eval) { $result .='}'; $eval = false; }
                        $result .= '->';
                        $mark = $i+1;
                        $state = self::ST_MEMBER;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                        $state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                        $state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                    }
                    else {
                        $result .= substr( $str, $mark, $i-$mark );
                        if ($eval) { $result .='}'; $eval = false; }
                        $state = self::ST_NONE;
                        $i--;
                    }   
                    break;

                case self::ST_DEFINE:
                    if (self::isVarNameChar($c)) {
                    }
                    else {
                        $state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark );
                        $i--;
                    }
                    break;
                    
                case self::ST_STATIC:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '$') {
                    }
                    else if ($c == '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        $result .= '->';
                        $mark = $i+1;
                        $state = self::ST_MEMBER;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark+1 );
                    }
                    else {
                        $result .= substr( $str, $mark, $i-$mark );
                        $state = self::ST_NONE;
                        $i--;
                    }   
                    break;

                case self::ST_NUM:
                    if ($c < '0' && $c > '9' && $c != '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        $state = self::ST_NONE;
                    }
                    break;
            }
        }

        return trim($result);
    }

    private static function isAlpha( $c )
    {
        $c = strtolower($c);
        return $c >= 'a' && $c <= 'z';
    }

    private static function isVarNameChar( $c )
    {
        return self::isAlpha($c) || ($c >= '0' && $c <= '9') || $c == '_';
    }

    private static $TranslationTable = array(
        'not' => '!', 
        'ne'  => '!=', 
        'and' => '&&',
        'or'  => '||',
        'lt'  => '<',
        'gt'  => '>',
        'ge'  => '>=',
        'le'  => '<=',
        'eq'  => '==',
    );
}

?>
