
(()=>{
 const form=document.getElementById('prototypeForm'),summary=document.getElementById('prototypeSummary'),modal=document.getElementById('contractModal'),content=document.getElementById('contractContent');
 const euro=new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
 const val=n=>(form.elements[n]?.value||'').trim();
 const checked=n=>!!form.elements[n]?.checked;
 function summaryText(){
   return `Projekt: ${val('project_name')||'–'}
Einsatz: ${val('use_case')||'–'} · Räuchergut: ${val('use_food')||'–'}
Ziel: ${val('goal')||'–'}

Abmessungen
Gesamtlänge: ${val('length')?val('length')+' cm':'offen'}
Drahtstärke: ${val('diameter')?val('diameter')+' mm':'offen'}
Anzahl Dornen: ${val('prongs')||'offen'}
Dornlänge: ${val('prong_length')?val('prong_length')+' cm':'offen'}
Öffnungsweite: ${val('opening')?val('opening')+' cm':'offen'}
Hakenform: ${val('shape')||'offen'}

Material: ${new FormData(form).get('material')||'VA'}
Spitzenausführung: ${val('tip')||'Standard geschärft'}
Oberfläche: ${val('surface')||'offen'}
Gewünschte Belastbarkeit: ${val('target_load')||'offen'}

Weitere Hinweise: ${val('dimensions_note')||'keine'}
Dateihinweise: ${val('file_note')||'keine'}

Preis Prototypenentwicklung: 149,00 € inkl. 19 % MwSt.`;
 }
 function update(){summary.textContent=summaryText()}
 form.addEventListener('input',update);update();

 const files=document.getElementById('prototypeFiles'),fileList=document.getElementById('fileList');
 files.addEventListener('change',()=>{fileList.innerHTML=[...files.files].map(f=>`<span class="fileChip">${f.name} · ${(f.size/1024/1024).toFixed(2)} MB</span>`).join('')});

 function contractHtml(){
   const rights=checked('commercial_rights')?'Ja – vorbehaltlich gesonderter schriftlicher Vereinbarung':'Nein';
   return `<article class="contractDoc">
   <h1>Prototypenauftrag – Räucherhaken24</h1>
   <p><b>Entwurf zur Prüfung vor Auftragserteilung</b></p>
   <h2>1. Auftraggeber</h2><p>${val('customer_name')||'–'}<br>${val('street')||'–'}<br>${val('zip')||'–'} ${val('city')||''}<br>E-Mail: ${val('email')||'–'}<br>Telefon: ${val('phone')||'–'}</p>
   <h2>2. Projektgegenstand</h2><pre style="white-space:pre-wrap;font:inherit">${summaryText()}</pre>
   <h2>3. Leistungsumfang</h2><p>Räucherhaken24 prüft die eingereichten Angaben, stimmt die technische Spezifikation mit dem Auftraggeber ab und fertigt nach ausdrücklicher Freigabe einen Prototypen. Serienfertigung, weitere Prototypen und nachträgliche Änderungen sind nicht Bestandteil des Pauschalpreises, soweit nicht schriftlich anders vereinbart.</p>
   <h2>4. Preis</h2><p>149,00 € inkl. 19 % MwSt. für die vereinbarte Prototypenentwicklung.</p>
   <h2>5. Kundendaten und Unterlagen</h2><p>Der Auftraggeber bestätigt, dass seine Angaben nach bestem Wissen richtig sind und dass er über die erforderlichen Rechte an hochgeladenen Bildern, Zeichnungen und Unterlagen verfügt.</p>
   <h2>6. Technische Prüfung</h2><p>Die endgültige technische Ausführung wird erst nach Prüfung und Freigabe festgelegt. Angaben zu Tragfähigkeit, Lebensmitteleignung oder besonderen Materialeigenschaften gelten nur, soweit sie in der final freigegebenen Spezifikation ausdrücklich bestätigt werden.</p>
   <h2>7. Rechte an Entwicklung und Sortimentsaufnahme</h2><p>Optionale Zustimmung zur späteren Weiterentwicklung / möglichen Sortimentsaufnahme: <b>${rights}</b>. Eine spätere kommerzielle Nutzung oder Rechteübertragung bedarf einer gesonderten schriftlichen Vereinbarung über Umfang, Dauer und etwaige Vergütung. Die Erteilung des Prototypenauftrags ist hiervon unabhängig.</p>
   <h2>8. Hinweis zur Vertragsvorlage</h2><p>Diese automatisch erzeugte Fassung ist ein technischer Vertragsentwurf für die Shopabwicklung. Vor Liveeinsatz sollten insbesondere die Regelungen zu geistigem Eigentum, Nutzungsrechten, Sonderanfertigungen, Widerruf, Haftung und Gewährleistung rechtlich geprüft und finalisiert werden.</p>
   <p style="margin-top:35px">Ort/Datum ____________________ &nbsp;&nbsp;&nbsp; Unterschrift Auftraggeber ____________________</p>
   </article>`;
 }
 document.getElementById('previewContract').onclick=()=>{content.innerHTML=contractHtml();modal.classList.add('show')};
 document.querySelector('.contractClose').onclick=()=>modal.classList.remove('show');
 document.getElementById('printContract').onclick=()=>{content.innerHTML=contractHtml();modal.classList.add('show');setTimeout(()=>window.print(),150)};
 document.getElementById('emailSummary').onclick=()=>{
   const subject=encodeURIComponent('Prototypenanfrage Räucherhaken24 – '+(val('project_name')||'neues Projekt'));
   const body=encodeURIComponent(summaryText()+'\n\nKunde: '+val('customer_name')+'\nE-Mail: '+val('email')+'\nTelefon: '+val('phone'));
   location.href=`mailto:service@raeucherhaken24.com?subject=${subject}&body=${body}`;
 };
 form.addEventListener('submit',async e=>{
   e.preventDefault();
   if(!form.reportValidity())return;
   const fd=new FormData(form);fd.append('summary',summaryText());fd.append('contract_html',contractHtml());
   try{
     const r=await fetch('prototype-submit.php',{method:'POST',body:fd});
     const data=await r.json();
     if(!r.ok||!data.ok)throw new Error(data.error||'Übertragung fehlgeschlagen');
     alert('Ihre Prototypenanfrage wurde übermittelt. Vorgangsnummer: '+data.reference);
   }catch(err){
     const subject=encodeURIComponent('Prototypenanfrage Räucherhaken24 – '+(val('project_name')||'neues Projekt'));
     const body=encodeURIComponent(summaryText()+'\n\nHinweis: Bitte die ausgewählten Skizzen/Dateien manuell an diese E-Mail anhängen.');
     if(confirm('Die direkte Serverübertragung ist in dieser Vorschau nicht verfügbar. E-Mail-Entwurf öffnen?'))location.href=`mailto:service@raeucherhaken24.com?subject=${subject}&body=${body}`;
   }
 });
 const buyBtn=document.getElementById('buyPrototypeProject');
 if(buyBtn) buyBtn.addEventListener('click',()=>{
   if(!form.reportValidity()) return;
   const project={
     id:'prototype-project',
     name:'Prototypenentwicklung Räucherhaken',
     price:149.00,
     qty:1,
     unit:'Projekt',
     meta:{
       project_name:val('project_name'),
       material:new FormData(form).get('material')||'VA',
       length:val('length'),
       diameter:val('diameter'),
       tip:val('tip'),
       customer_name:val('customer_name'),
       customer_email:val('email'),
       summary:summaryText()
     }
   };
   try{
     let cart=[];
     try{cart=JSON.parse(localStorage.getItem('rh24cart')||'[]')}catch(e){}
     const line={id:'prototype-project',key:'prototype-project',qty:1,meta:{...project.meta,unitPrice:149}};
     const idx=cart.findIndex(x=>x.id==='prototype-project');
     if(idx>=0) cart[idx]=line; else cart.push(line);
     localStorage.setItem('rh24cart',JSON.stringify(cart));
     localStorage.setItem('prototypeProjectPending',JSON.stringify(project));
     alert('Projekt wurde dem Warenkorb hinzugefügt. Projektstart nach Zahlungseingang. Fertigungszeit ca. 4–6 Wochen.');
     location.href='shop.html#cart';
   }catch(e){
     alert('Das Projekt konnte nicht in den Warenkorb gelegt werden. Bitte versuchen Sie es erneut.');
   }
 });

})();
