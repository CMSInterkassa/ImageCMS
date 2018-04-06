<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>-->
<form name="payment_interkassa" id="InterkassaForm" action="javascript:selpayIK.selPaysys()" method="POST" class="">
    {foreach $data as $field => $value}
    <input type="hidden" name="{echo $field}" value="{echo $value}" />
    {/foreach}
    <div class='btn-cart btn-cart-p'>
        <input type="submit" value="{lang('Оплатить', 'payment_method_interkassa')}">
    </div>
</form>

<div class="interkasssa" style="text-align: center;">
    {if is_array($payment_systems) && !empty($payment_systems)}
        <button type="button" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal" data-target="#InterkassaModal" style="display: none;">
            Select Payment Method
        </button>
        <div id="InterkassaModal" class="ik-modal fade" role="dialog">
            <div class="ik-modal-dialog ik-modal-lg">
                <div class="ik-modal-content" id="plans">
                    <div class="container">
                        <h3>
                            1. {lang('Выберите удобный способ оплаты', 'payment_method_interkassa')}<br>
                            2. {lang('Укажите валюту', 'payment_method_interkassa')}<br>
                            3. {lang('Нажмите &laquo;Оплатить&raquo;', 'payment_method_interkassa')}<br>
                        </h3>

                        <div class="row">
                            {foreach $payment_systems as $ps => $info}
                                <div class="col-sm-3 text-center payment_system">
                                    <div class="panel panel-warning panel-pricing">
                                        <div class="panel-heading">
                                            <div class="panel-image">
                                                <img src="{echo $image_path . $ps}.png"
                                                     alt="{echo $info['title']}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <div class="radioBtn btn-group">
                                                    {foreach $info['currency'] as $currency => $currencyAlias}
                                                        <a class="btn btn-primary btn-sm notActive"
                                                           data-toggle="fun"
                                                           data-title="{echo $currencyAlias}">{echo $currency}</a>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel-footer">
                                            <a class="btn btn-lg btn-block btn-success ik-payment-confirmation"
                                               data-title="{echo $ps}"
                                               href="#">{lang('Оплатить через', 'payment_method_interkassa')}<br>
                                                <strong>{echo $info['title']}</strong>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {else:}
        {echo $payment_systems}
    {/if}
</div>
<script>
    var interkassa_lang = []
    interkassa_lang.error_selected_currency  = "{lang('Вы не выбрали валюту', 'payment_method_interkassa')}"
    interkassa_lang.something_wrong  = "{lang('Что-то пошло не так', 'payment_method_interkassa')}"
</script>