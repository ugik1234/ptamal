<?php if (!defined('BASEPATH')) exit('No direct script access allowed');



if (!function_exists('fetch_single_qty_item')) {
	//USED TO FETCH AND COUNT THE NUMBER OF OCCURANCE IN STOCK
	function fetch_single_qty_item($item_id)
	{
		$CI	= &get_instance();
		$CI->load->database();
		$CI->db->select("qty");
		$CI->db->from('mp_sales');
		$CI->db->where(['mp_sales.product_id' => $item_id]);

		$query = $CI->db->get();
		$result = NULL;
		if ($query->num_rows() > 0) {
			$obj_res =  $query->result();
			if ($obj_res != NULL) {
				foreach ($obj_res as $single_qty) {
					$result = $result + $single_qty->qty;
				}
			}
		}

		return $result;
	}
}

if (!function_exists('tanggal_indonesia')) {
	function tanggal_indonesia($tanggal)
	{
		if (empty($tanggal)) return '';
		$BULAN = [
			0, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
		];
		$t = explode('-', $tanggal);
		return "{$t[2]} {$BULAN[intval($t[1])]} {$t[0]}";
	}
}

if (!function_exists('romawi_bulan')) {
	function romawi_bulan($bln)
	{
		// echo $bln;
		switch ($bln) {
			case 1:
				return "I";
				break;
			case 2:
				return "II";
				break;
			case 3:
				return "III";
				break;
			case 4:
				return "IV";
				break;
			case 5:
				return "V";
				break;
			case 6:
				return "VI";
				break;
			case 7:
				return "VII";
				break;
			case 8:
				return "VIII";
				break;
			case 9:
				return "IX";
				break;
			case 10:
				return "X";
				break;
			case 11:
				return "XI";
				break;
			case 12:
				return "XII";
				break;
		}
	}
}


if (!function_exists('fetch_single_pending_item')) {
	//USED TO FETCH AND COUNT THE NUMBER OF OCCURANCE IN PENDING STOCK
	function fetch_single_pending_item($item_id)
	{
		$CI	= &get_instance();
		$CI->load->database();
		$CI->db->select("qty");
		$CI->db->from('mp_stock');
		$CI->db->where(['mp_stock.mid' => $item_id]);

		$query = $CI->db->get();
		$result = 0;
		if ($query->num_rows() > 0) {
			$obj_res =  $query->result();
			if ($obj_res != NULL) {
				foreach ($obj_res as $single_qty) {
					$result = $result + $single_qty->qty;
				}
			}
		}

		return $result;
	}
}

if (!function_exists('fetch_single_return_item')) {
	//USED TO FETCH AND COUNT THE NUMBER OF OCCURANCE IN RETURN STOCK
	function fetch_single_return_item($item_id)
	{
		$CI	= &get_instance();
		$CI->load->database();
		$CI->db->select("qty");
		$CI->db->from('mp_return_list');
		$CI->db->where(['mp_return_list.product_id' => $item_id]);
		$query = $CI->db->get();
		$result = 0;
		if ($query->num_rows() > 0) {
			$obj_res =  $query->result();
			if ($obj_res != NULL) {
				foreach ($obj_res as $single_qty) {
					$result = $result + $single_qty->qty;
				}
			}
		}

		return $result;
	}
}

if (!function_exists('color_options')) {
	//USED TO FETCH AND COUNT THE NUMBER OF OCCURANCE IN RETURN STOCK
	function color_options()
	{
		$CI	= &get_instance();
		$CI->load->database();
		$color_arr = $CI->db->get_where('mp_langingpage', array('id' => 1))->result_array()[0];
		return  array('primary' => $color_arr['primarycolor'], 'hover' => $color_arr['theme_pri_hover']);
	}
}


// ------------------------------------------------------------------------
/* End of file helper.php */
/* Location: ./system/helpers/Side_Menu_helper.php */