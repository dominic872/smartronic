function calculateTotal() {
  const cameras = parseInt(document.getElementById('cameraCount').value) || 0;
  const cable = parseFloat(document.getElementById('cableLength').value) || 0;
  const kmRange = document.getElementById('kmRange').value;
  const tv = document.getElementById('tvInstalled').checked;
  const rack = document.getElementById('rackInstalled').checked;
  const extra = parseFloat(document.getElementById('extraValue').value) || 0;

  const bnc = cameras * 2;
  document.getElementById('bncCount').value = bnc;

  let total = 0;
  
  const threshold = (cable/cameras) > 25  ;

  if (threshold || kmRange === '1' || kmRange === '2') {
    total += 100 * cameras;
  }
  total += cameras * 500;
  total += bnc * 25;
  if (tv) total += 300;
  if (rack) total += 300;
  total += extra;

  document.getElementById('totalCost').innerText = total.toFixed(2);
}
