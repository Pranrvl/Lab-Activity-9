let selectedFiles = [];
const dropZone    = document.getElementById('dropZone');
const inputFoto   = document.getElementById('inputFoto');
const previewGrid = document.getElementById('previewGrid');

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('dragover');
  handleFiles(e.dataTransfer.files);
});
inputFoto.addEventListener('change', () => { handleFiles(inputFoto.files); inputFoto.value = ''; });

function handleFiles(list) {
  Array.from(list).forEach(file => {
    if (!['image/jpeg','image/jpg'].includes(file.type)) {
      showFotoErr(`"${file.name}" bukan file JPG.`); return;
    }
    if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) return;
    selectedFiles.push(file);
    addPreview(file, selectedFiles.length - 1);
  });
  updateCounter();
}

function addPreview(file, idx) {
  const reader = new FileReader();
  reader.onload = e => {
    const el = document.createElement('div');
    el.className = 'preview-item';
    el.innerHTML = `
      <img src="${e.target.result}" alt="${esc(file.name)}"/>
      <button class="rm" type="button" onclick="removeFile(${idx})">✕</button>
      <div class="fn">${esc(file.name)}</div>`;
    previewGrid.appendChild(el);
  };
  reader.readAsDataURL(file);
}

function removeFile(idx) {
  selectedFiles.splice(idx, 1);
  previewGrid.innerHTML = '';
  selectedFiles.forEach((f, i) => addPreview(f, i));
  updateCounter();
}

function updateCounter() {
  const c = document.getElementById('fileCounter');
  const b = document.getElementById('fileBadge');
  if (selectedFiles.length > 0) {
    c.style.display = 'block';
    b.textContent = `${selectedFiles.length} file dipilih`;
  } else {
    c.style.display = 'none';
  }
}

function showFotoErr(msg) {
  const el = document.getElementById('fotoError');
  el.textContent = msg;
  el.style.setProperty('display', 'block', 'important');
  setTimeout(() => el.style.setProperty('display', 'none', 'important'), 4000);
}

function validate() {
  let ok = true;
  const nik  = document.getElementById('inputNIK');
  const nama = document.getElementById('inputNama');
  nik.classList.remove('is-invalid');
  nama.classList.remove('is-invalid');
  if (!nik.value.trim())  { nik.classList.add('is-invalid');  ok = false; }
  if (!nama.value.trim()) { nama.classList.add('is-invalid'); ok = false; }
  if (selectedFiles.length === 0) { showFotoErr('Pilih minimal 1 foto JPG.'); ok = false; }
  return ok;
}

function submitForm() {
  document.getElementById('resultPanel').style.display = 'none';
  if (!validate()) return;

  var fd = new FormData();
  fd.append('nik',  document.getElementById('inputNIK').value.trim());
  fd.append('nama', document.getElementById('inputNama').value.trim());
  selectedFiles.forEach(function(f) { fd.append('foto[]', f, f.name); });

  var overlay = document.getElementById('loadingOverlay');
  var btn     = document.getElementById('btnSubmit');
  overlay.classList.add('active');
  btn.disabled = true;

  $.ajax({
    url: "server/save_mahasiswa.php",
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function(result, statusText, xhr) {
      overlay.classList.remove('active');
      btn.disabled = false;
      handleXML(result, xhr.responseText);
    },
    error: function(xhr, statusText, error) {
      overlay.classList.remove('active');
      btn.disabled = false;
      showResult('GAGAL', '', '', [], 'Error: ' + error);
    }
  });
}

function handleXML(xmlDoc, rawText) {
  var getVal = function(tag) {
    var el = xmlDoc.getElementsByTagName(tag)[0];
    return (el && el.childNodes.length > 0) ? el.childNodes[0].nodeValue : '';
  };

  var status = getVal("status");
  var nik = getVal("nik");
  var nama = getVal("nama");

  var urls = [];
  var fotoNodes = xmlDoc.getElementsByTagName("url");
  for(var i = 0; i < fotoNodes.length; i++) {
     urls.push(fotoNodes[i].childNodes[0].nodeValue);
  }

  showResult(status, nik, nama, urls, rawText);
}

function showResult(status, nik, nama, urls, raw) {
  const panel = document.getElementById('resultPanel');
  panel.style.display = 'block';

  const ok = status === 'SUKSES';
  document.getElementById('resStatus').className = 'result-status ' + (ok ? 'sukses' : 'gagal');
  document.getElementById('resIcon').className   = 'bi ' + (ok ? 'bi-check-circle-fill' : 'bi-x-circle-fill');
  document.getElementById('resStatusTxt').textContent = 'Status: ' + status;

  const grid = document.getElementById('resGrid');
  if (nik) {
    grid.style.display = 'grid';
    document.getElementById('resNIK').textContent  = nik  || '—';
    document.getElementById('resNama').textContent = nama || '—';
    const list = document.getElementById('resFotoList');
    list.innerHTML = '';
    (urls.length > 0 ? urls : ['(tidak ada foto)']).forEach(function(u) {
      const li = document.createElement('li');
      li.textContent = u;
      list.appendChild(li);
    });
  } else {
    grid.style.display = 'none';
  }

  document.getElementById('xmlRaw').textContent = raw;
  panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  if (ok) {
    loadData();
  }
}

document.getElementById('modalRegistrasi').addEventListener('hidden.bs.modal', function() {
  document.getElementById('formReg').reset();
  document.getElementById('inputNIK').classList.remove('is-invalid');
  document.getElementById('inputNama').classList.remove('is-invalid');
  selectedFiles = [];
  previewGrid.innerHTML = '';
  updateCounter();
  document.getElementById('resultPanel').style.display = 'none';
  document.getElementById('resGrid').style.display = 'none';
});

function esc(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
           .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}


$(document).ready(function() {
  loadData();
});

function loadData() {
  var tbody = document.querySelector('#tabelMahasiswa tbody');
  tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Memuat data...</td></tr>';

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var xmlDoc = this.responseXML;
      tbody.innerHTML = '';
      
      var x = xmlDoc.getElementsByTagName("mahasiswa");
      if (x.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Belum ada mahasiswa terdaftar.</td></tr>';
        return;
      }

      var tableRows = "";
      for (var i = 0; i < x.length; i++) {
        var nikNode = x[i].getElementsByTagName("nik")[0].childNodes[0];
        var namaNode = x[i].getElementsByTagName("nama")[0].childNodes[0];
        var fotoNode = x[i].getElementsByTagName("foto")[0].childNodes[0];

        var nik = nikNode ? nikNode.nodeValue : '';
        var nama = namaNode ? namaNode.nodeValue : '';
        var fotoUrl = (fotoNode && fotoNode.nodeValue !== "null") ? fotoNode.nodeValue : '';

        var imgHtml = fotoUrl 
          ? '<img src="' + fotoUrl + '" alt="Foto" style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;">' 
          : '<span class="text-muted">—</span>';

        tableRows += '<tr style="vertical-align: middle;">' +
                     '<td class="px-3"><strong>' + (i + 1) + '</strong></td>' +
                     '<td class="px-3">' + imgHtml + '</td>' +
                     '<td class="px-3">' + nik + '</td>' +
                     '<td class="px-3">' + nama + '</td>' +
                     '</tr>';
      }
      tbody.innerHTML = tableRows;
    }
  };
  xhttp.open("GET", "server/tampilkan_mahasiswa.php", true);
  xhttp.send();
}


