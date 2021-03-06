<?php
/*

*/
class Statment_model_new extends CI_Model
{


    public function getAllJournalVoucher($filter = [])
    {
        $this->db->select("gen.id as parent_id,generated_source,gen.ref_url, paye.customer_name, sub.id as sub_id,gen.ref_number,gen.date,gen.naration,gen.customer_id,gen.user_update,sub.accounthead,sub.amount,sub.type,sub.sub_keterangan,head.name as head_name, head_number    ");
        $this->db->from('dt_generalentry as gen');
        $this->db->join('mp_sub_entry as sub', "gen.id = sub.parent_id", 'LEFT');
        $this->db->join('dt_head as head', "head.id = sub.accounthead", 'LEFT');
        $this->db->join('mp_payee as paye', "paye.id = gen.customer_id", 'LEFT');
        if (!empty($filter['id'])) $this->db->where('gen.id', $filter['id']);
        if (!empty($filter['source'])) $this->db->where('gen.generated_source', $filter['source']);
        if (!empty($filter['from'])) $this->db->where('gen.date >=', $filter['from']);
        if (!empty($filter['to'])) $this->db->where('gen.date <=', $filter['to']);
        if (!empty($filter['search'])) {
            $this->db->where('gen.ref_number like "%' . $filter['search'] . '%" OR gen.naration like "%' . $filter['search'] . '%"');
        }
        $this->db->order_by('gen.date, gen.id,  sub.id ', 'DESC');
        $res = $this->db->get();
        // echo json_encode(DataStructure::groupBy2($query->result_array(), 'parent_id', 'parent_id', ['parent_id', 'name', 'order_number'], 'items'));
        if (!empty($filter['by_id'])) {
            return DataStructure::keyValue($res->result_array(), 'parent_id');
        }
        $ret = DataStructure::renderJurnal(
            // $ret = DataStructure::groupByRecursive2(
            $res->result_array(),
            ['parent_id'],
            ['sub_id'],
            [
                ['parent_id', 'generated_source', 'ref_number', 'ref_url', 'date', 'naration', 'customer_id', 'user_update', 'customer_name'],
                ['sub_id', 'accounthead', 'head_name', 'amount', 'type', 'sub_keterangan', 'head_number']
            ],
            ['children'],
            false
        );
        // $ret = $res->result_array();
        // echo json_encode($ret);
        // die();
        return $ret;
    }

    public function the_ledger($data, $filter = [])
    {
        foreach ($data as $k => $s) {
            $data[$k]['open'] = true;
            foreach ($data[$k]['children'] as $l => $t) {
                $data[$k]['children'][$l]['open'] = false;
                // echo $l;
                foreach ($data[$k]['children'][$l]['children'] as $m => $u) {
                    $cur = $this->get_ledger_transactions($u['id_head'], $filter);
                    if (!empty($cur)) {
                        $data[$k]['open'] = true;
                        $data[$k]['children'][$l]['open'] = true;
                        $data[$k]['children'][$l]['children'][$m]['data'] = $cur;
                    }
                }
            }
        }
        return $data;
    }

    public function trail_balance($data, $filter = [])
    {
        $income = 0;
        foreach ($data as $k) {
            if (substr($k['head_number'], 1, 6) == '00000') {
                if ($k['nature'] == 'Equity') {
                    $income = $this->income($filter);
                }
                $res[substr($k['head_number'], 0, 1)] = array('head_number' => substr($k['head_number'], 0, 1), 'name' => $k['name']);
                $res[substr($k['head_number'], 0, 1)]['children'] = array();
            } else if (substr($k['head_number'], 3, 3) == '000') {
                $cur_2 = $this->akumulasi_head_number(substr($k['head_number'], 0, 3), $filter);
                // if ($k['nature'] == 'Equity')
                //     $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)] =  array('open' => true, 'head_number' => substr($k['head_number'], 1, 2), 'name' => $k['name'], 'children' => array());
                $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)] =  array('open' => false, 'head_number' => substr($k['head_number'], 1, 2), 'name' => $k['name'], 'data' => $cur_2, 'children' => array());
            } else {
                $cur = $this->get_trail_balance($k['id'], $filter);
                if (!empty($cur)) {
                    // echo json_encode($cur);
                    // die();
                    $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['open'] = true;
                    $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)] =  array('head_number' =>  substr($k['head_number'], 3, 3), 'name' => $k['name'], 'id_head' => $k['id'], 'data' => $cur);
                }
                if ($k['nature'] == 'Equity') {
                    if (!empty($filter['akum_laba_rugi']['akum_laba_rugi']['ref_account'])) {
                        if ($filter['akum_laba_rugi']['akum_laba_rugi']['ref_account'] == $k['id']) {
                            $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['open'] = true;
                            $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)] =  array('head_number' =>  substr($k['head_number'], 3, 3), 'name' => $k['name'], 'id_head' => $k['id'], 'data' => $income);
                            // echo json_encode($res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)]);
                            // die();
                            // die();
                        }
                    }
                }
            }
        }
        return $res;
    }

    public function cash_flow($filter = [])
    {

        // tester
        // $this->db->select("dt_generalentry.id as transaction_id,dt_generalentry.date,dt_generalentry.ref_number,dt_generalentry.naration,dt_generalentry.ref_number,mp_sub_entry.*");
        // $this->db->select("s2.id id_s2 ,h2.head_number h2");
        // $this->db->from('mp_sub_entry');
        // $this->db->join('dt_head', "dt_head.id = mp_sub_entry.accounthead");
        // $this->db->join('mp_sub_entry as s2', "s2.parent_id = mp_sub_entry.parent_id AND s2.type <> mp_sub_entry.type ");
        // $this->db->join('dt_head h2', "h2.id = s2.accounthead");

        // $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        // $this->db->where('h2.head_number NOT LIKE  "101%"');
        // $this->db->where('dt_generalentry.id > 0');
        // $this->db->where('mp_sub_entry.parent_id > 0');
        // if (!empty($filter['search'])) {
        //     $this->db->where('(mp_sub_entry.sub_keterangan like "%' . $filter['search'] . '%" OR dt_generalentry.naration like "%' . $filter['search'] . '%")');
        // }

        // $this->db->where('dt_head.head_number like "101%"');
        // // $this->db->where('dt_generalentry.date >=', $date1);
        // // $this->db->where('dt_generalentry.date <=', $date2);
        // $this->db->order_by('dt_generalentry.date', 'asc');
        // $this->db->group_by('id', 'asc');
        // $res = $this->db->get();
        // $ret = DataStructure::detectCashFlow(
        //     $res->result_array()
        // );

        // return $ret;
        // // $res['transactions'] = $query->result_array();
        // echo json_encode($ret);
        // die();
        // end tester
        $this->db->select("gen.id");
        $this->db->from('dt_generalentry as gen');
        $this->db->join('mp_sub_entry as sub', "sub.parent_id = gen.id");
        $this->db->join('dt_head as head', "head.id = sub.accounthead");
        $this->db->where('head.head_number like "101%"');
        if (!empty($filter['date_start'])) $this->db->where('gen.date >=', $filter['date_start']);
        if (!empty($filter['date_end'])) $this->db->where('gen.date <=', $filter['date_end']);
        $this->db->group_by('gen.id');
        $q1 = $this->db->get();
        $q1 = $q1->result_array();
        $gen_arr = array();
        foreach ($q1 as $d) {
            $gen_arr[] = $d['id'];
        }
        // echo json_encode($gen_arr);
        // die();
        // if (!empty($gen_arr)) {
        $this->db->select("gen.id as parent_id,generated_source,gen.ref_url, paye.customer_name, sub.id as sub_id,gen.ref_number,gen.date,gen.naration,gen.customer_id,gen.user_update,sub.accounthead,sub.amount,sub.type,sub.sub_keterangan,head.name as head_name, head_number    ");
        $this->db->from('dt_generalentry as gen');
        $this->db->join('mp_sub_entry as sub', "gen.id = sub.parent_id", 'LEFT');
        $this->db->join('dt_head as head', "head.id = sub.accounthead", 'LEFT');
        $this->db->join('mp_payee as paye', "paye.id = gen.customer_id", 'LEFT');

        if (!empty($gen_arr))
            $this->db->where_in('gen.id', $gen_arr);
        else
            $this->db->where_in('gen.id', false);
        $this->db->order_by('gen.date, gen.id, head.head_number', 'DESC');
        $res = $this->db->get();
        // echo json_encode(DataStructure::groupBy2($query->result_array(), 'parent_id', 'parent_id', ['parent_id', 'name', 'order_number'], 'items'));
        if (!empty($filter['by_id'])) {
            return DataStructure::keyValue($res->result_array(), 'parent_id');
        }
        $ret = DataStructure::renderCF(
            // $ret = DataStructure::groupByRecursive2(
            $res->result_array(),
            ['parent_id'],
            ['sub_id'],
            [
                ['parent_id', 'generated_source', 'ref_number', 'ref_url', 'date', 'naration', 'customer_id', 'user_update', 'customer_name'],
                ['sub_id', 'accounthead', 'head_name', 'amount', 'type', 'sub_keterangan', 'head_number']
            ],
            ['children'],
            false
        );
        // } else {
        //     $ret = array();
        // }
        // $ret = $res->result_array();
        if ($filter['tahun'] == '2022' && $filter['bulan'] == '1') $ret['total']['saldo_sebelum'] = 135601145.66;
        else {
            $ret['total']['saldo_sebelum'] = $this->akumulasi_head_number('101', array('date_end' => $filter['date_start']));
        }
        // echo json_encode($ret);
        // die();
        return $ret;
    }

    public function cash_flow2($data, $filter = [])
    {
        // tester
        // $this->db->select("
        // SUM(IF(SUBSTRING(head_number,1,3) = '101',IF(sub.type = '0', amount, -amount),0)) as am_bank,
        // IF(SUBSTRING(head_number,1,3) = '401','REV USAHA',
        //     IF(SUBSTRING(head_number,1,3) = '102','PIUT USAHA', '-')
        // ) as GEN,
        //   ,gen.id as parent_id,generated_source,gen.ref_url, paye.customer_name, gen.ref_number,gen.date,gen.naration,gen.customer_id,gen.user_update,sub.accounthead,sub.amount,sub.type,sub.sub_keterangan,head.name as head_name, head_number    ");
        // $this->db->from('dt_generalentry as gen');
        // $this->db->join('mp_sub_entry as sub', "gen.id = sub.parent_id", 'LEFT');
        // $this->db->join('dt_head as head', "head.id = sub.accounthead", 'LEFT');
        // $this->db->join('mp_payee as paye', "paye.id = gen.customer_id", 'LEFT');

        // $this->db->group_by('gen.id');
        // $this->db->order_by('gen.date, gen.id', 'DESC');
        // $res = $this->db->get();
        // $res = $res->result_array();
        // echo json_encode($res);
        // die();
        // end tester
        $this->db->select("gen.id");
        $this->db->from('dt_generalentry as gen');
        $this->db->join('mp_sub_entry as sub', "sub.parent_id = gen.id");
        $this->db->join('dt_head as head', "head.id = sub.accounthead");
        $this->db->where('head.head_number like "101%"');
        if (!empty($filter['date_start'])) $this->db->where('gen.date >=', $filter['date_start']);
        if (!empty($filter['date_end'])) $this->db->where('gen.date <=', $filter['date_end']);
        $this->db->group_by('gen.id');
        $q1 = $this->db->get();
        $q1 = $q1->result_array();
        $gen_arr = array();
        foreach ($q1 as $d) {
            // if (!empty($idName)) $ret[] = [$key => $d, $idName => $counter++];
            $gen_arr[] = $d['id'];
        }
        // echo json_encode($gen_arr);
        // die();
        if (!empty($gen_arr)) {

            $this->db->select("gen.id as parent_id,generated_source,gen.ref_url, paye.customer_name, sub.id as sub_id,gen.ref_number,gen.date,gen.naration,gen.customer_id,gen.user_update,sub.accounthead,sub.amount,sub.type,sub.sub_keterangan,head.name as head_name, head_number    ");
            $this->db->from('dt_generalentry as gen');
            $this->db->join('mp_sub_entry as sub', "gen.id = sub.parent_id", 'LEFT');
            $this->db->join('dt_head as head', "head.id = sub.accounthead", 'LEFT');
            $this->db->join('mp_payee as paye', "paye.id = gen.customer_id", 'LEFT');
            $this->db->where_in('gen.id', $gen_arr);
            // if (!empty($filter['source'])) $this->db->where('gen.generated_source', $filter['source']);
            // if (!empty($filter['from'])) $this->db->where('gen.date >=', $filter['from']);
            // if (!empty($filter['to'])) $this->db->where('gen.date <=', $filter['to']);
            // if (!empty($filter['search'])) {
            //     $this->db->where('gen.ref_number like "%' . $filter['search'] . '%" OR gen.naration like "%' . $filter['search'] . '%"');
            // }
            $this->db->order_by('gen.date, gen.id,  sub.id ', 'DESC');
            $res = $this->db->get();
            // echo json_encode(DataStructure::groupBy2($query->result_array(), 'parent_id', 'parent_id', ['parent_id', 'name', 'order_number'], 'items'));
            if (!empty($filter['by_id'])) {
                return DataStructure::keyValue($res->result_array(), 'parent_id');
            }
            $ret = DataStructure::renderJurnal(
                // $ret = DataStructure::groupByRecursive2(
                $res->result_array(),
                ['parent_id'],
                ['sub_id'],
                [
                    ['parent_id', 'generated_source', 'ref_number', 'ref_url', 'date', 'naration', 'customer_id', 'user_update', 'customer_name'],
                    ['sub_id', 'accounthead', 'head_name', 'amount', 'type', 'sub_keterangan', 'head_number']
                ],
                ['children'],
                false
            );
        } else {
            $ret = array();
        }
        // $ret = $res->result_array();
        // echo json_encode($ret);
        // die();
        $datas = [];
        // $datas['out_general']['children'] = [];
        $datas['out_general']['children'] = [];
        $datas['out_pajak']['children'] = [];
        $datas['out_usaha']['children'] = [];
        $datas['in_bank']['children'] = [];
        $datas['in_dll']['children'] = [];
        $datas['in_usaha']['children'] = [];
        $datas['piutang_bank']['children'] = [];
        $datas['inves_pinjaman']['children'] = [];



        // Kegiatan operasi
        $datas['out_general']['value'] = 0;
        $datas['out_pajak']['value'] = 0;
        $datas['out_usaha']['value'] = 0;

        $datas['in_bank']['value'] = 0;
        $datas['in_dll']['value'] = 0;
        $datas['in_usaha']['value'] = 0;
        $datas['piutang_bank']['value'] = 0;
        //kegiatan investasi
        $datas['inves_pinjaman']['value'] = 0;
        foreach ($ret as $d) {
            // if(substr($d))
            $type = '';
            $am_bank = 0;
            foreach ($d['children'] as $e) {
                if (substr($e['head_number'], 0, 3) == '101') {
                    $am_bank = $am_bank + ($e['type'] == 0 ? $e['amount'] : -$e['amount']);
                } else if (substr($e['head_number'], 0, 3) == '501') {
                    // echo json_encode($e);
                    // die();
                    $datas['out_general']['value'] = $datas['out_general']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '502') { // done out usaha
                    $datas['out_usaha']['value'] = $datas['out_usaha']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '403') { // done pend lain 
                    $datas['in_dll']['value'] = $datas['in_dll']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '503') { // done output pajak
                    $datas['out_pajak']['value'] = $datas['out_pajak']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '402') { //done pend bank
                    $datas['in_bank']['value'] = $datas['in_bank']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '103' or substr($e['head_number'], 0, 3) == '401') {  //done pend usaha lewat piutang dan langsung
                    $datas['in_usaha']['value'] = $datas['in_usaha']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                    $datas['in_usaha']['children'][] = array(
                        'id' => $d['parent_id'],
                        'ref_number' => $d['ref_number'],
                        'naration' => $d['naration'],
                        'amount' => $e['amount']
                    );
                } else if (substr($e['head_number'], 0, 3) == '104') {
                    $datas['piutang_bank']['value'] = $datas['piutang_bank']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                } else if (substr($e['head_number'], 0, 3) == '203') {
                    $datas['inves_pinjaman']['value'] = $datas['inves_pinjaman']['value'] + ($e['type'] == 1 ?  $e['amount'] : -$e['amount']); //ok
                };
            }
        } {
            // foreach ($ret as $d) {
            //     // if(substr($d))
            //     $type = '';
            //     $am_bank = 0;
            //     foreach ($d['children'] as $e) {
            //         if (substr($e['head_number'], 0, 3) == '101') {
            //             $am_bank = $am_bank + ($e['type'] == 0 ? $e['amount'] : -$e['amount']);
            //         } else if (substr($e['head_number'], 0, 3) == '501') {
            //             // $datas['out_general']['value'] = $datas['out_general']['value'] + ($e['type'] == 0 ? -$e['amount'] : $e['amount']);
            //             $type = 'out_general';
            //         } else if (substr($e['head_number'], 0, 3) == '502') { // done out usaha
            //             $type = 'out_usaha';
            //         } else if (substr($e['head_number'], 0, 3) == '403') { // done pend lain 
            //             $type = 'in_dll';
            //         } else if (substr($e['head_number'], 0, 3) == '503') { // done output pajak
            //             $type = 'out_pajak';
            //         } else if (substr($e['head_number'], 0, 3) == '402') { //done pend bank
            //             $type = 'in_bank';
            //         } else if (substr($e['head_number'], 0, 3) == '103' or substr($e['head_number'], 0, 3) == '401') {  //done pend usaha lewat piutang dan langsung
            //             $type = 'in_usaha';
            //         } else if (substr($e['head_number'], 0, 3) == '104') {
            //             $type = 'piutang_bank';
            //         } else if (substr($e['head_number'], 0, 3) == '203') {
            //             $type = 'inves_pinjaman';
            //         };
            //     }

            //     if ($type == 'out_general') {
            //         $datas['out_general']['value'] = $datas['out_general']['value'] + $am_bank; //ok
            //         // echo json_encode($d);
            //         // die();
            //         $datas['out_general']['children'][] = array(
            //             'id' => $d['parent_id'],
            //             'ref_number' => $d['ref_number'],
            //             'naration' => $d['naration'],
            //             'amount' => $am_bank
            //         );
            //     }
            //     if ($type == 'in_usaha')
            //         $datas['in_usaha']['value'] = $datas['in_usaha']['value'] + $am_bank; // ok
            //     if ($type == 'out_usaha')
            //         $datas['out_usaha']['value'] = $datas['out_usaha']['value'] + $am_bank;
            //     if ($type == 'in_dll')
            //         $datas['in_dll']['value'] = $datas['in_dll']['value'] + $am_bank; // ok
            //     if ($type == 'in_bank')
            //         $datas['in_bank']['value'] = $datas['in_bank']['value'] + $am_bank; // ok
            //     if ($type == 'out_pajak')
            //         $datas['out_pajak']['value'] = $datas['out_pajak']['value'] + $am_bank;
            //     if ($type == 'inves_pinjaman')
            //         $datas['inves_pinjaman']['value'] = $datas['inves_pinjaman']['value'] + $am_bank;
            // }
        }

        $total['inves'] =
            $datas['inves_pinjaman']['value'];
        $total['operasi'] =
            $datas['out_general']['value'] +
            $datas['in_usaha']['value'] +
            $datas['out_usaha']['value'] +
            $datas['in_dll']['value'] +
            $datas['piutang_bank']['value'] +
            $datas['in_bank']['value'] +
            $datas['out_pajak']['value'];
        // foreach ($datas as $key => $dts) {
        //     $total['operasi'] = $total['operasi'] + $dts['value'];
        // }
        $datas['total'] = $total;
        return $datas;
        // return array('data' => $datas, 'd' => $d);
        echo json_encode(array('data' => $datas, 'total' => $total, 'd' => $d));
        die();


        // $jv = $this->getAllJournalVoucher($filter);
        // echo json_encode($jv);
        // die();
        // $income = 0;
        // $datax[] = array('label' => 'Arus KAS dari Aktiits Operasi');
        // $datax[0]['Pelanggan'] = $this->akumulasi_head_number(substr($k['head_number'], 0, 3), $filter);

        // foreach ($data as $k) {
        //     if (substr($k['head_number'], 1, 6) == '00000') {
        //         if ($k['nature'] == 'Equity') {
        //             $income = $this->income($filter);
        //         }
        //         $res[substr($k['head_number'], 0, 1)] = array('head_number' => substr($k['head_number'], 0, 1), 'name' => $k['name']);
        //         $res[substr($k['head_number'], 0, 1)]['children'] = array();
        //     } else if (substr($k['head_number'], 3, 3) == '000') {
        //         $cur_2 = $this->akumulasi_head_number(substr($k['head_number'], 0, 3), $filter);
        //         // if ($k['nature'] == 'Equity')
        //         //     $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)] =  array('open' => true, 'head_number' => substr($k['head_number'], 1, 2), 'name' => $k['name'], 'children' => array());
        //         $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)] =  array('open' => false, 'head_number' => substr($k['head_number'], 1, 2), 'name' => $k['name'], 'data' => $cur_2, 'children' => array());
        //     } else {
        //         $cur = $this->get_trail_balance($k['id'], $filter);
        //         if (!empty($cur)) {
        //             // echo json_encode($cur);
        //             // die();
        //             $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['open'] = true;
        //             $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)] =  array('head_number' =>  substr($k['head_number'], 3, 3), 'name' => $k['name'], 'id_head' => $k['id'], 'data' => $cur);
        //         }
        //         if ($k['nature'] == 'Equity') {
        //             if (!empty($filter['akum_laba_rugi']['akum_laba_rugi']['ref_account'])) {
        //                 if ($filter['akum_laba_rugi']['akum_laba_rugi']['ref_account'] == $k['id']) {
        //                     $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['open'] = true;
        //                     $res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)] =  array('head_number' =>  substr($k['head_number'], 3, 3), 'name' => $k['name'], 'id_head' => $k['id'], 'data' => $income);
        //                     // echo json_encode($res[substr($k['head_number'], 0, 1)]['children'][substr($k['head_number'], 1, 2)]['children'][substr($k['head_number'], 3, 3)]);
        //                     // die();
        //                     // die();
        //                 }
        //             }
        //         }
        //     }
        // }
        // return $datax;
    }

    public function get_trail_balance($head_id, $filter = [])
    {
        $count_total_amt = 0;
        $this->db->select('ROUND(sum(IF(mp_sub_entry.type = 0,  mp_sub_entry.amount,-mp_sub_entry.amount)),2) as amount');
        // $this->db->select("dt_generalentry.id as transaction_id,dt_generalentry.date,dt_generalentry.naration,dt_generalentry.ref_number,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        $this->db->where('mp_sub_entry.accounthead', $head_id);
        if (!empty($filter['date_start'])) $this->db->where('dt_generalentry.date >=', $filter['date_start']);
        if (!empty($filter['date_end'])) $this->db->where('dt_generalentry.date <=', $filter['date_end']);

        $query = $this->db->get();
        return  $query->result_array()[0]['amount'];
    }

    public function akumulasi_head_number($head_number, $filter = [])
    {
        $count_total_amt = 0;
        $this->db->select('ROUND(sum(IF(mp_sub_entry.type = 0,  mp_sub_entry.amount,-mp_sub_entry.amount)),2) as amount');
        // $this->db->select("dt_generalentry.id as transaction_id,dt_generalentry.date,dt_generalentry.naration,dt_generalentry.ref_number,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        $this->db->join('dt_head', 'dt_head.id = mp_sub_entry.accounthead');
        $this->db->where('dt_head.head_number like "' . $head_number . '%"');
        if (!empty($filter['date_start'])) $this->db->where('dt_generalentry.date >=', $filter['date_start']);
        if (!empty($filter['date_end'])) $this->db->where('dt_generalentry.date <=', $filter['date_end']);

        $query = $this->db->get();
        // echo $head_number;
        return $query->result_array()[0]['amount'];
        // die();
    }

    public function income($filter = [])
    {
        $nature = array('Expense', 'Revenue');
        $this->db->select('ROUND(sum(IF(mp_sub_entry.type = 0,  mp_sub_entry.amount,-mp_sub_entry.amount)),2) as amount');
        $this->db->from('mp_sub_entry');
        $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        $this->db->join('dt_head', 'dt_head.id = mp_sub_entry.accounthead');
        $this->db->where_in('dt_head.nature', $nature);
        if (!empty($filter['date_start'])) $this->db->where('dt_generalentry.date >=', $filter['date_start']);
        if (!empty($filter['date_end'])) $this->db->where('dt_generalentry.date <=', $filter['date_end']);
        $query = $this->db->get();
        return  $query->result_array()[0]['amount'];
    }


    public function get_ledger_transactions($head_id, $filter)
    {

        $date1 = $filter['date_start'];
        $date2 = $filter['date_end'];
        $this->db->select("SUM(IF(mp_sub_entry.type = 0,amount, -amount)) as saldo_awal");
        $this->db->from('mp_sub_entry');
        $this->db->join('dt_head', "dt_head.id = mp_sub_entry.accounthead");
        $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        $this->db->order_by('dt_generalentry.date', 'asc');
        $year = explode('-', $date1)[0];
        $this->db->where('dt_head.id', $head_id);
        if ($date1 == $year . '-1-1' or $date1 == $year . '-01-1' or $date1 == $year . '-1-01' or $date1 == $year . '-01-01') {
            $this->db->where('dt_generalentry.id = -' . explode('-', $date1)[0]);
            $this->db->where('mp_sub_entry.parent_id = -' . explode('-', $date1)[0]);
        } else {
            $this->db->where('dt_generalentry.date >=', $year . '-1-1');
            $this->db->where('dt_generalentry.date <', $date1);
        }
        $query = $this->db->get();
        $query = $query->result_array();
        if (empty($query)) {
            $saldo_awal = 0;
        } else {
            $saldo_awal = $query[0]['saldo_awal'];
        }
        $this->db->select("dt_generalentry.id as transaction_id,dt_generalentry.date,dt_generalentry.ref_number,dt_generalentry.naration,dt_generalentry.ref_number,mp_sub_entry.*");
        $this->db->from('mp_sub_entry');
        $this->db->join('dt_head', "dt_head.id = mp_sub_entry.accounthead");
        $this->db->join('dt_generalentry', 'dt_generalentry.id = mp_sub_entry.parent_id');
        $this->db->where('dt_generalentry.id > 0');
        $this->db->where('mp_sub_entry.parent_id > 0');
        if (!empty($filter['search'])) {
            $this->db->where('(mp_sub_entry.sub_keterangan like "%' . $filter['search'] . '%" OR dt_generalentry.naration like "%' . $filter['search'] . '%")');
        }

        $this->db->where('dt_head.id', $head_id);
        $this->db->where('dt_generalentry.date >=', $date1);
        $this->db->where('dt_generalentry.date <=', $date2);
        $this->db->order_by('dt_generalentry.date', 'asc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $res['transactions'] = $query->result_array();
            $res['saldo_awal'] = $saldo_awal;
            return $res;
        } else {
            return NULL;
        }
    }
}
