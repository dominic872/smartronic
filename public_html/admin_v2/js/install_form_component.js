(function (window) {
  'use strict';

  function renderInstallFormMarkup() {
    return `
      <div class="form-row">
        <div class="input-group">
          <label for="id">ID</label>
          <input type="text" id="id" name="id" placeholder="ID" required>
        </div>
        <div class="input-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" placeholder="Name" required>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="resolution">Res</label>
          <select id="resolution" name="resolution" required>
            <option value="">Resolution</option>
            <option value="2 MP">2 MP</option>
            <option value="5 MP">5 MP</option>
          </select>
        </div>
        <div class="input-group">
          <label for="cams">Total Cams</label>
          <input type="number" id="cams" name="cams" placeholder="Total Cams" required>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="bullets">Bullets</label>
          <input type="number" id="bullets" name="bullets" placeholder="Bullets">
        </div>
        <div class="input-group">
          <label for="dome">Dome</label>
          <input type="number" id="dome" name="dome" placeholder="Dome">
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="type">Type</label>
          <select id="type" name="type">
            <option value="">Select Type</option>
            <option value="DVR">DVR</option>
            <option value="NVR">NVR</option>
            <option value="WIFI">WIFI</option>
          </select>
        </div>
        <div class="input-group">
          <label for="hdd">HDD</label>
          <select id="hdd" name="hdd">
            <option value="">Select HDD</option>
            <option value="500GB">500GB</option>
            <option value="1TB">1TB</option>
            <option value="2TB">2TB</option>
            <option value="3TB">3TB</option>
            <option value="4TB">4TB</option>
            <option value="6TB">6TB</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="monitor">Monitor</label>
          <select id="monitor" name="monitor">
            <option value="">Select Monitor</option>
            <option value="15 Inches">15 Inches</option>
            <option value="19 Inches">19 Inches</option>
            <option value="22 Inches">22 Inches</option>
            <option value="24 Inches">24 Inches</option>
          </select>
        </div>
        <div class="input-group">
          <label for="rack">Rack</label>
          <select id="rack" name="rack">
            <option value="">Rack</option>
            <option value="2U">2U</option>
            <option value="4U">4U</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="Location">
        </div>
        <div class="input-group">
          <label for="map">Map Link</label>
          <input type="text" id="map" name="map" placeholder="Map Link">
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="time">Time</label>
          <input type="time" id="time" name="time">
        </div>
        <div class="input-group">
          <label for="date">Date</label>
          <input type="date" id="date" name="date" required>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="owner">Owner</label>
          <select id="owner" name="owner">
            <option value="">Select Owner</option>
            <option value="VAR">VAR</option>
            <option value="AMR">AMR</option>
            <option value="ZOY">ZOY</option>
            <option value="DOM">DOM</option>
          </select>
        </div>
        <div class="input-group">
          <label for="technician">Technician</label>
          <select id="technician" name="technician">
            <option value="">Select Technician</option>
            <option value="SYED">SYED</option>
            <option value="KARTHICK">KARTHICK</option>
            <option value="ABDUL">ABDUL</option>
            <option value="DAVID">DAVID</option>
            <option value="PAWAN">PAWAN</option>
            <option value="SIREN">SIREN</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="input-group">
          <label for="helper">Helper</label>
          <select id="helper" name="helper">
            <option value="">Select Helper</option>
            <option value="KARTHIK">KARTHIK</option>
            <option value="ABDUL">ABDUL</option>
            <option value="SYED 2">SYED 2</option>
            <option value="Harish">Harish</option>
          </select>
        </div>
        <div class="input-group">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes" rows="2" placeholder="Notes"></textarea>
        </div>
      </div>

      <div class="inline-edit-actions">
        <button type="submit" class="btn-base btn-primary">Save</button>
      </div>
    `;
  }

  function mountRequirementForm(targetFormEl) {
    if (!targetFormEl) return null;
    targetFormEl.classList.add('compact-form');
    targetFormEl.innerHTML = renderInstallFormMarkup();
    return {
      getValues: function () {
        const get = (id) => {
          const el = targetFormEl.querySelector('#' + id);
          return el ? (el.value || '') : '';
        };
        return {
          id: get('id'),
          name: get('name'),
          cams: get('cams'),
          bullets: get('bullets'),
          dome: get('dome'),
          hdd: get('hdd'),
          monitor: get('monitor'),
          type: get('type'),
          location: get('location'),
          time: get('time'),
          date: get('date'),
          owner: get('owner'),
          technician: get('technician'),
          helper: get('helper'),
          resolution: get('resolution'),
          map: get('map'),
          rack: get('rack'),
          notes: get('notes')
        };
      },
      getDate: function () {
        const el = targetFormEl.querySelector('#date');
        return el ? (el.value || '') : '';
      }
    };
  }

  window.SmartronicComponents = window.SmartronicComponents || {};
  window.SmartronicComponents.InstallRequirementForm = {
    mount: mountRequirementForm
  };
})(window);

