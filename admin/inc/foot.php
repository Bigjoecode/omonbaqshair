</div><!-- content -->
</div><!-- main -->
<script>
// auto-dismiss alerts
setTimeout(()=>document.querySelectorAll('.alert-success').forEach(a=>a.style.display='none'),4000);
// confirm deletes
document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm))e.preventDefault();}));
</script>
</body></html>
