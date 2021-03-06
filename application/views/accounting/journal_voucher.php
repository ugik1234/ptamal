<style>
    .info-jurnal {
        text-align: left;
    }

    .info-jurnal .head-info-1 {
        /* text-align: left; */
        width: 100px;
        vertical-align: top;
    }

    .info-jurnal td {
        /* text-align: left; */
        /* width: 100px; */
        vertical-align: top;
    }
</style>
<div class="card card-custom position-relative overflow-hidden">
    <!--begin::Shape-->
    <div class="container">
        <div class="alert alert-custom alert-white alert-shadow gutter-b" role="alert">
            <?php
            $attributes = array('id' => 'general_journal', 'method' => 'get', 'class' => 'form col-lg-12');
            ?>
            <?php echo form_open_multipart('statement', $attributes); ?>
            <div class="col-lg-12">
                <div class="col-lg-12">
                    <!-- <div class="col-lg-3"> -->
                    <div style="float: right" class="form-group" style="margin-top: 16px;">
                        <a class="btn btn-default btn-outline-primary  mr-2" href="<?= base_url() ?>accounting/journal_voucher"> <i class="fa fa-plus" aria-hidden="true"></i> Entry New</a>
                    </div>

                    <div style="float: right" class="form-group" style="margin-top: 16px;">
                        <?php
                        $data = array('class' => 'btn btn-default btn-outline-primary  mr-2', 'type' => 'button', 'id' => 'btn_export_excel', 'value' => 'true', 'content' => '<i class="fa fa-download" aria-hidden="true"></i> Export Excel');
                        echo form_button($data);
                        ?>
                    </div>
                    <!-- </div> -->
                    <!-- <div class="col-lg-3"> -->
                    <div style="float: right" class="form-group" style="margin-top: 16px;">
                        <a onclick="printDiv('print-section')" class="btn btn-default btn-outline-primary  mr-2"><i class="fa fa-print  pull-left"></i> Cetak</a>
                    </div>
                    <!-- </div> -->
                </div>
                <div class="row col-lg-12">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo form_label('No Jurnal'); ?>
                            <?php
                            $data = array('class' => 'form-control input-lg', 'type' => 'text', 'id' => 'ref_number', 'name' => 'ref_number', 'reqiured' => '', 'value' => !empty($filter['ref_number']) ? $filter['ref_number'] : '');
                            echo form_input($data);
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo form_label('Search'); ?>
                            <?php
                            $data = array('class' => 'form-control input-lg', 'type' => 'text', 'id' => 'search', 'name' => 'search', 'reqiured' => '', 'value' => !empty($filter['search']) ? $filter['search'] : '');
                            echo form_input($data);
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-3 ">
                        <div class="form-group">
                            <?php echo form_label('Dari Tanggal'); ?>
                            <?php
                            $data = array('class' => 'form-control input-lg', 'type' => 'date', 'id' => 'from', 'name' => 'from', 'reqiured' => '', 'value' => $filter['from']);
                            echo form_input($data);
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-3 ">
                        <div class="form-group">
                            <?php echo form_label('Sampai Tanggal'); ?>
                            <?php
                            $data = array('class' => 'form-control input-lg', 'type' => 'date', 'id' => 'to', 'name' => 'to', 'reqiured' => '', 'value' => $filter['to']);
                            echo form_input($data);
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group" style="margin-top: 24px; float: right">
                            <button class="btn btn-info btn-flat mr-2" type="submit" name="btn_submit_customer" value="true"> <i class=" fa fa-search pull-left"></i> Buat Statement</button>

                        </div>
                    </div>
                </div>
            </div>
            <?php form_close(); ?>
        </div>
        <div class="card card-custom" id="print-section">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3"></div>
                    <div class="col-lg-6">
                        <h2 style="text-align:center">JURNAL UMUM </h2>
                        <h3 style="text-align:center">
                            <?php echo $this->db->get_where('mp_langingpage', array('id' => 1))->result_array()[0]['companyname'];
                            ?>
                        </h3>
                        <h4 style="text-align:center"><b>Dari</b> <?php echo $filter['from']; ?> <b> Sampai </b> <?php echo $filter['to']; ?>
                        </h4>
                        <h4 style="text-align:center">Dibuat <?php echo Date('Y-m-d'); ?>
                        </h4>
                    </div>
                    <div class="col-lg-12">
                        <table class="datatable-table table-striped table-hover" style="width : 100%" id="">
                            <thead class="ledger_head" style="width : 30%">
                                <th style="width: 108px;">TANGGAL</th>
                                <th style="width: 408px;" colspan='2'>AKUN</th>
                                <!-- <th class="">KETERANGAN</th> -->
                                <th class="">DEBIT</th>
                                <th class="">KREDIT</th>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($journals as $parent) {
                                    // var_dump($parent);
                                    if (!empty($parent['ref_url'])) {
                                        // echo explode('/', $parent['ref_url'])[0];
                                        $btn_lock =  '<a href="' . base_url() . $parent['ref_url'] . '" class="btn btn-default btn-outline-primary mr-1 my-1 no-print" style="float: right"><i class="fa fa-list-alt pull-left"></i> ' . ucfirst(explode('/', $parent['ref_url'])[0]) . ' </a> ' .
                                            ($vcrud['view'] == 1 ? '<a href="' . base_url() . 'accounting/show_journal/' . $parent['parent_id'] . '" class="btn btn-default btn-outline-primary mr-1 my-1 no-print" style="float: right"><i class="fa fa-eye pull-left"></i> Show </a> ' : '');
                                    } else {
                                        $btn_lock =  ($vcrud['hk_delete'] == 1 ? '<a href="' . base_url() . 'accounting/delete_jurnal/' . $parent['parent_id'] . '" class="btn btn-default btn-outline-danger mr-1 my-1 no-print" style="float: right"><i class="fa fa-trash  pull-left"></i> Delete </a> ' : '') .
                                            ($vcrud['hk_update'] == 1 ? ($parent['generated_source'] == 'Journal Voucher' ? '<a href="' . base_url() . 'accounting/edit_jurnal/' . $parent['parent_id'] . '" class="btn btn-default btn-outline-primary mr-1 my-1 no-print" style="float: right"><i class="fa fa-list-alt pull-left"></i> Edit </a> ' : '') : '') .
                                            ($vcrud['view'] == 1 ? '<a href="' . base_url() . 'accounting/show_journal/' . $parent['parent_id'] . '" class="btn btn-default btn-outline-primary mr-1 my-1 no-print" style="float: right"><i class="fa fa-eye pull-left"></i> Show </a> ' : '');
                                    }

                                    echo '<tr class="narration" >
                                        <td class="border-bottom-journal" colspan="5" style=" text-align: center;">
                                        <div class="row">
                                            <div class="col-md-9" style="text-align: left; margin: auto">
                                            <table class="info-jurnal">
                                            <tr> <td class="head-info-1">No Jurnal</td> <td> : </td> <td> ' . $parent['ref_number'] . ' </td> </tr>
                                              <tr>  <td class="head-info-1">Deskripsi </td> <td > : </td> <td> ' . $parent['naration'] . ' </td> </tr>
                                              ' . (!empty($parent['customer_name']) ? '    <tr><td class="head-info-1">Mitra </td> <td > : </td> <td> ' . $parent['customer_name'] . ' </td> </tr>' : '') . '
                                            </table>
                                             </div>
                                            <div class="col-md-3"> ' .  $btn_lock . ' </div>
                                        </div>
                                       </td>
                                        </tr>';
                                    // $parent['naration'] . ' '
                                    // $child = DataStructure::associativeToArray($parent['children']);

                                    // $child = DataStructure::array_sort_by_column($child, 'type');
                                    // uasort($child, function ($a, $b) {
                                    // $res = strcmp($a['type'], $b['type']);
                                    // });
                                    // {
                                    // usort($$parent['children'])

                                    // }

                                    foreach ($parent['children'] as $single_trans) {
                                        // foreach ($child as $single_trans) {

                                        // <td>
                                        // <p>'  . $single_trans['sub_keterangan'] . '</p>
                                        //     </td>
                                        echo '<tr>
                                        <td style=" text-align: center; width: 100px">' . $parent['date'] . '</td>
                                        <td style=" text-align: center; width: 100px">
                                        [' . $single_trans['head_number'] . ']</td><td style=" text-align: left;">' . $single_trans['head_name'] . '
                                        </td>';
                                        if ($single_trans['type'] == 0) {
                                            echo '
                                            <td>
                                                <p>' . number_format($single_trans['amount'], 2, ',', '.') . '</p>
                                            </td>
                                            <td>
                                            </td>          
                                            ';
                                        } else if ($single_trans['type'] == 1) {
                                            echo ' <td>
                                            </td>
                                            <td>
                                            <p >' . number_format($single_trans['amount'], 2, ',', '.')  . '</p>
                                            </td>          
                                            ';
                                        }
                                        echo '</tr>';
                                    }
                                    // var_dump($child);
                                    // die();

                                    echo '<tr class="" >
                                    <td class="" colspan="5" style=" text-align: center;"> <hr style="border : 2px solid #b8d3ff ">
                                    </td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script>
    $('#menu_id_24').addClass('menu-item-active menu-item-open menu-item-here"')
    $('#submenu_id_59').addClass('menu-item-active')

    function formatRupiah(angka, prefix) {
        var number_string = angka.toString(),
            split = number_string.split("."),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        // tambahkan titik jika yang di input sudah menjadi angka ribuan
        if (ribuan) {
            separator = sisa ? "." : "";
            rupiah += separator + ribuan.join(".");
        }

        rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
        return prefix == undefined ? rupiah : rupiah ? "Rp. " + rupiah : "";
    }

    var elements = document.getElementsByClassName('currency')
    for (var i = 0; i < elements.length; i++) {
        elements[i].innerHTML = formatRupiah(elements[i].innerHTML);
    }

    $('#btn_export_excel').on('click', function() {
        console.log('s')
        from = $('#from').val()
        to = $('#to').val()
        url = `<?= base_url('statements/export_excel?from=') ?>` + from + '&to=' + to;
        location.href = url;
    })
</script>
<!-- Bootstrap model  -->
<?php $this->load->view('bootstrap_model.php'); ?>
<!-- Bootstrap model  ends-->