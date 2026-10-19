<section class="iss-card">
    <div class="iss-card__body">
        <div class="d-flex align-items-center gap-3">
            <div><h2>{$PROGRESS}</h2></div>
            <div id="loader"><span class="spinner-border spinner-border-sm" role="status"></span></div>
            <div class="checkwrapper" id="check"><svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"> <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/> <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg></div>
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
