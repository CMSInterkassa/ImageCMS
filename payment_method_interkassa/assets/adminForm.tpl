<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Главная валюта', 'payment_method_interkassa')} :</label>
    <div class="controls maincurrText">
        <span>{echo \Currency\Currency::create()->getMainCurrency()->getName()}</span>
        <span>({echo \Currency\Currency::create()->getMainCurrency()->getCode()})</span>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Валюта оплаты услуг', 'payment_method_interkassa')} :</label> {/*}Валюта оплаты услуг{ */}
    <div class="controls">
        {foreach \Currency\Currency::create()->getCurrencies() as $currency}
            {if in_array($currency->getCode(), array('RUB', 'UAH', 'USD', 'EUR', 'BYR', 'XAU'))}
            <label>
                <input type="radio" name="payment_method_interkassa[merchant_currency]"
                       {if $data['merchant_currency']}
                           {if $data['merchant_currency'] == $currency->getId()}
                               checked="checked"
                           {/if}    
                       {else:}
                           {if \Currency\Currency::create()->getMainCurrency()->getId() == $currency->getId()}
                               checked="checked"
                           {/if} 
                       {/if}
                       value="{echo $currency->getId()}"
                       />
                <span>{echo $currency->getName()}({echo $currency->getCode()})</span>
            </label>
            {/if}

        {/foreach}
    </div>
</div>
{/*}
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Наценка', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" onkeyup="checkLenghtStr('oldP', 3, 2, event.keyCode);" id="oldP" name="payment_method_interkassa[merchant_markup]" value="{echo $data['merchant_markup']}"/> %
    </div>
</div>
{ */}
<br/>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Id кассы', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" name="payment_method_interkassa[id_cashbox]" value="{echo $data['id_cashbox']}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Секретный ключ', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" name="payment_method_interkassa[secret_key]" value="{echo $data['secret_key']}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Test secret key', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" name="payment_method_interkassa[test_key]" value="{echo $data['test_key']}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Тестовый режим', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="checkbox" name="payment_method_interkassa[test_mode]" {if $data['test_mode']}checked='checked'{/if}/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Включить API', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="checkbox" name="payment_method_interkassa[enableAPI]" {if $data['enableAPI']}checked='checked'{/if}/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('API id', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" name="payment_method_interkassa[api_id]" value="{echo $data['api_id']}"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('API ключ', 'payment_method_interkassa')} :</label>
    <div class="controls">
        <input type="text" name="payment_method_interkassa[api_key]" value="{echo $data['api_key']}"/>
    </div>
</div>
<div class="control-group">
    <div class="controls">
        <p>{lang('URL успешной оплаты:', 'payment_method_interkassa')}</p>
        <p style="margin-left:50px">{lang('POST, разрешить переопределять в запросе', 'payment_method_interkassa')}</p>
        <p>{lang('URL неуспешной оплаты:', 'payment_method_interkassa')}</p>
        <p style="margin-left:50px">{lang('POST, разрешить переопределять в запросе', 'payment_method_interkassa')}</p>
        <p>{lang('URL ожидания проведения платежа:', 'payment_method_interkassa')}</p>
        <p style="margin-left:50px">{lang('POST, разрешить переопределять в запросе', 'payment_method_interkassa')}</p>
        <p>{lang('URL взаимодействия:', 'payment_method_interkassa')}</p>
        <p style="margin-left:50px">{lang('POST', 'payment_method_interkassa')},  {echo site_url('/payment_method_interkassa/callback')}</p>
    </div>				
</div>