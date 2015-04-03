function check_summ(elem){

    var activInputId = elem.id;
    
    arr = activInputId.split("_");
    
    var nameLec = 'nag_lec_' + arr[2] +'_'+ arr[3];
    var namePrakzan = 'nag_prakzan_' + arr[2] +'_'+ arr[3];
    var nameLabzan = 'nag_labzan_' + arr[2] +'_'+ arr[3];
    var nameKSR = 'nag_ksr_' + arr[2] +'_'+ arr[3];
    var nameAud = 'nag_aud_' + arr[2] +'_'+ arr[3];
      
    var lec = parseFloat(document.getElementById(nameLec).value);
    if (isNaN(lec)) {
        lec = 0;
    }
    var prakzan = parseFloat(document.getElementById(namePrakzan).value);
    if (isNaN(prakzan)) {
        prakzan = 0;
    }
    var labzan = parseFloat(document.getElementById(nameLabzan).value);
    if (isNaN(labzan)) {
        labzan = 0;
    }
    var ksr = parseFloat(document.getElementById(nameKSR).value);
    if (isNaN(ksr)) {
        ksr = 0;
    }

    
    document.getElementById(nameAud).value = lec + prakzan + labzan + ksr;
}