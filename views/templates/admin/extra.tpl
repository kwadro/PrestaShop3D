{*
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
* @author    Cappasity Inc <info@cappasity.com>
* @copyright 2014-2017 Cappasity Inc.
* @license   http://cappasity.us/eula_modules/  Cappasity EULA for Modules
*}
<div class="panel cappasity-preview-container row" style="{($currentFile)?'':'display: none'|escape:'htmlall':'UTF-8'}">
    <h3>{l s='Preview' mod='cappasity3d'}</h3>

    <div class="cappasity-embed col-sm-9">
        {($currentFile)?$currentFile->getEmbed(true):''|escape:'htmlall':'UTF-8'}
    </div>

    <div class="form-horizontal col-sm-3">
        <div class="panel-footer">
            <input class="cappasity-id" type="hidden" name="cappasityId" value="{($currentFile)?$currentFile->getId():''|escape:'htmlall':'UTF-8'}">
            <input class="cappasity-action" type="hidden" name="cappasityAction" value="">

            <button type="submit" name="submitAddproduct" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>Save
            </button>
            <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>Save and stay
            </button>
            <button id="cappasity-action-button" type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right">
                <i class="process-icon-delete"></i>Delete
            </button>
        </div>
    </div>
</div>

<div class="panel">
    <h3>{l s='Cappasity 3D' mod='cappasity3d'}</h3>
    <div class="cappasity-list"
         data-url="{$action|escape:'htmlall':'UTF-8'}&action=ModuleCappasity3d&id_product={$productId|escape:'htmlall':'UTF-8'}&updateproduct&ajax=1&subaction=list">
    </div>
</div>

<script>
  {literal}
  function preview() {
    var container = $('.cappasity-preview-container');
    var id = $(this).data('id');
    var embed = $(this).data('embed');

    $('.cappasity-id').val(id);
    $('.cappasity-embed').html(embed);

    container.show();

    $('body, html').animate({ scrollTop: 0 }, 800);
  }

  function paginate(page, query) {
    var listContainer = $('.cappasity-list');
    var url = listContainer.data('url') + '&page=' + page + '&query=' + query;

    listContainer.html('<img src="/img/loader.gif">');

    $.get(url, function (content) {
      listContainer.html(content);
    });
  }

  function initCappasity() {
    var page = 1;
    var query = '';

    paginate(page, query);

    $(document).on('click', '.cappasity-list .cappasity-model', preview);

    $(document).on('click', '.cappasity-list .cappasity-search', function() {
        var currentQuery = $('.cappasity-search-input').val();

        if (currentQuery !== query) {
          page = 1;
        }

        query = currentQuery;

        paginate(page, query);
    });

    $(document).on('click', '.cappasity-list .cappasity-paginate', function(event) {
      event.preventDefault();
      page = $(this).data('page');

      paginate(page, query);
    });

    $('#cappasity-action-button').on('click', function() {
      $('.cappasity-action').val('remove');
    });
  }

  $(document).ready(initCappasity);
  {/literal}
</script>
