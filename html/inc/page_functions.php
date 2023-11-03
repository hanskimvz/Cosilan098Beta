<?php
function strInput($label, $id, $value, $size=0, $readonly=0) {
    $size = $size ? " col-md-".$size : "";
    $readonly = $readonly ? " readonly": "";
    if (!$label) {
        return '<input type="hidden" id="'.$id.'" value="'.$value.'">';
    }

    $str = '<div class="form-group'.$size.'">
        <label class="col-form-label">'.$label.'</label>
        <input type="text" id="'.$id.'" value="'.$value.'" class="form-control"'.$readonly.'>
    </div>';
    return $str;
}

function strTextarea($label, $id, $value, $size=0) {
    $size = $size ? " col-md-".$size : "";
    $str = '<div class="form-group'.$size.'">
        <label class="col-form-label">'.$label.'</label>
        <textarea id="'.$id.'" class="form-control">'.$value.'</textarea>
    </div>';
    return $str;
}

function strSelect($label, $id, $arr_option, $selected, $size=0){
    $size = $size ? " col-md-".$size : "";
    $option_str = '';
    for($i=0; $i<sizeof($arr_option); $i++){
        $option_str .= $arr_option[$i]['value'] == $selected ? 
            '<option value="'.$arr_option[$i]['value'].'" selected>'.$arr_option[$i]['text'].'</option>' :
            '<option value="'.$arr_option[$i]['value'].'">'.$arr_option[$i]['text'].'</option>';
    }
    $str = '<select id="'.$id.'" class="form-control">'.$option_str.'</select>';
    if ($label) {
        $str = '<div class="form-group'.$size.'">
            <label>'.$label.'</label>
            '.$str.'
        </div>';
    }
    return $str;
}

function strRadio($label, $name, $arr_option, $checked, $size=0){
	$size = $size ? " col-md-".$size : "";
	$str = '';
	for($i=0; $i<sizeof($arr_option); $i++) {
		$chk_tag = $arr_option[$i]['value'] == $checked ? " checked" :"";
		$str .= '<label class="form-check-inline mt-1">
			<input class="form-check-input" type="radio" name="'.$name.'" value="'.$arr_option[$i]['value'].'"'.$chk_tag.'>
			<span class="form-check-label">'.$arr_option[$i]['text'].'</span>
		</label>';
	}
	$str = '<div class="form-group'.$size.'"><label>'.$label.'</label>
		<div class="form-group mb-0">'.$str.'</div>
	</div>';

	return $str;
}

// function strCheck($label, $id, $checked=0, $function_onChange=0) {
//     $checked = $checked ? "checked" : "";
//     if ($function_onChange) {
//         $function_onChange = 'OnChange="'.$function_onChange.'"';
//     }
//     $str = '<label class="form-check-inline col-md-2">
//         <input class="form-check-input" type="checkbox" id="'.$id.'" '.$function_onChange.' '.$checked.'>'.$label.'
//     </label>';

//     return $str;
// }
	
?>