

                <table class="table" class="table" style="border:1px solid #EEE;">

                    <tr>

                        <th style="text-align:left;border-bottom:1px solid #EEE;padding:5px;">Company:</th>

                        <td style="border-bottom:1px solid #EEE;padding:5px;"><?php echo $quot['quotations_company_name'];?></td>

                    </tr>

                    <tr>

                        <th style="text-align:left;border-bottom:1px solid #EEE;padding:5px;">Name:</th>

                        <td style="border-bottom:1px solid #EEE;padding:5px;"><?php echo $quot['quotations_name'];?></td>

                    </tr>

                    <tr>

                        <th style="text-align:left;border-bottom:1px solid #EEE;padding:5px;">Email:</th>

                        <td style="border-bottom:1px solid #EEE;padding:5px;"><?php echo $quot['quotations_email'];?></td>

                    </tr>

                    <tr>

                        <th style="text-align:left;border-bottom:1px solid #EEE;padding:5px;">Phone:</th>

                        <td style="border-bottom:1px solid #EEE;padding:5px;"><?php echo $quot['quotations_telephone'];?></td>

                    </tr>

                </table>

            </header>

        <div class="col-md-12">

            <h3>Quotation Form Response:</h3>

            <table class="table" style="border:1px solid #EEE;">

                <?php foreach($data as $d){

                    $d = (object)$d;

                ?>

                <tr style="">

                    <th style="text-align:left;border-bottom:1px solid #EEE;padding:5px;"><?php echo $d->question;?></th>

                    <td style="border-bottom:1px solid #EEE;padding:5px;"><?php echo $d->answer;?></td>

                </tr>

                <?php }?>

            </table>

        </div>
