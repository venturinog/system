<section class="iss-card">
    <div class="iss-card__body">
        <div class="d-flex align-items-center gap-3">
            <div><h2>{$PROGRESS}</h2></div>
            <div id="loader"><span class="spinner-border spinner-border-sm" role="status"></span></div>
            <i class="ti ti-circle-check text-success" id="check" style="display:none" aria-hidden="true"></i>
        </div>
        <div class="mt-3">
            <iframe name="ifm" id="myframe" frameborder=0 width="100%" height="500px" marginheight=0 marginwidth=0 scrolling=yes src={$frame_url}></iframe>
        </div>
    </div>
</section>
<script>
{literal}
pepe = setInterval('frames[0].scrollTo(0,9999999)',1000);
{/literal}
</script>
