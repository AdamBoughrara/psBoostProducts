<div class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i> {l s='Boost Product'}
    </div>
    <div class="panel-body">
        <form action="{{ path('admin_boost_product_boost') }}" method="post" class="form-horizontal">
            <input type="hidden" name="id_product" value="{{ product.id_product }}">
            <div class="form-group">
                <label class="col-lg-3 control-label">{l s='Boost duration (in days)'}:</label>
                <div class="col-lg-9">
                    <input type="number" name="boost_duration" class="form-control" value="1" min="1">
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-9">
                    <button type="submit" name="submitBoostProduct" class="btn btn-primary">
                        <i class="icon-money"></i> {l s='Boost product'}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
