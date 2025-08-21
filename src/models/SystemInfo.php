<?php
namespace croacworks\essentials\models;

use yii\base\Model;

/**
 * Utility class return system em hardware informations
 *
 */
class SystemInfo extends Model
{
    /**
     * Return disk information.
     * @param string $directory
     * @return array(float total_bytes, string total_bytes_pretty, float free_bytes, string free_bytes_pretty, string percent_used),
     */
    public function diskInfo($directory = '.')
    {
        $total_bytes = \disk_total_space($directory);
        $free_bytes = \disk_free_space($directory);
        $used_bytes = $total_bytes - $free_bytes;
        $percent_used = ($used_bytes/$total_bytes) * 100;

        return [
            'total_bytes'=>$total_bytes,
            'total_bytes_pretty'=>$this->formatBytes($total_bytes),
            'free_bytes'=>$free_bytes,
            'free_bytes_pretty'=>$this->formatBytes($free_bytes),
            'used_bytes'=>$free_bytes,
            'used_bytes_pretty'=>$this->formatBytes($used_bytes),
            'percent_used'=>round($percent_used, 2)
        ];
    }

    /**
     * Return operation system information.
     * @return array,
     */

    function getOSInformation()
    {
        $info = php_uname('v');
        if(str_contains('Windows',$info)){
            $info = php_uname('v');
            $regex = "/.*\(([^)]*)\)/";
            preg_match($regex,$info,$matches);

            return [
                "type"=>'windows',
                "pretty_name" => $matches[1],
                "name" => $info
            ];
            
        }else if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
            return null;
        }

        $os         = shell_exec('cat /etc/os-release');
        $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds    = $matchListIds[0];

        $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal    = $matchListVal[0];

        array_walk($listIds, function(&$v, $k){
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function(&$v, $k){
            $v = preg_replace('/=|"/', '', $v);
        });
        $info = array_combine($listIds, $listVal);
        $info['type'] = 'linux';
        return $info;
    }

    /**
     * Return cpu information.
     * @return array,
     */
    public function cpuInfo()
    {
        $used = \exec("top -bn2 | grep '%Cpu' | tail -1 | grep -P '(....|...) id,'|awk '{print 100-$8}'");
        return [
            'used'=> floatval($used),
            'free'=> floatval(100 - $used)
        ];
    }

    /**
     * Return memory information.
     * @return array,
     */
    public function memoryInfo()
    {
        $fields = [];
        $values = [];
        $infos = [];

        $cmd_fields = explode(' ',\exec('free -b | grep total'));
        $cmd_values = trim(explode('Mem:',\exec('free -b | grep Mem'))[1]);
        $cmd_values_array = explode(' ',$cmd_values);

        foreach($cmd_values_array as $info){
            if(!empty($info)){
                $values[] = $info;
            }
        }

        foreach($cmd_fields as $cmd_field){
            if(!empty($cmd_field)){
                $fields[] = $cmd_field;
            }
        }

        foreach($fields as $key => $field){
            $infos[$field] = $values[$key];
            $infos["{$field}_pretty"] = $this->formatBytes($values[$key]);
        }

        $memory_percent_used = ($infos['used']/$infos['total']) * 100;   
        $infos['percent_used'] = round($memory_percent_used, 2);

        return $infos;
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $type=array("", "Kb", "Mb", "Gb", "Tb", "Pb", "Eb", "Zb", "Yb");
        $index=0;
        while($bytes>=1024)
        {
          $bytes/=1024;
          $index++;
        }
        return("".round($bytes, $precision)." ".$type[$index]);
    }


}

?>