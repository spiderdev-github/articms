(function(){
  const alertEl = document.getElementById("contactAlert");
  if(alertEl){
    setTimeout(() => {
      alertEl.style.transition = "opacity 250ms ease, transform 250ms ease";
      alertEl.style.opacity = "0";
      alertEl.style.transform = "translateY(-6px)";
      setTimeout(() => alertEl.remove(), 300);
    }, 6000);
  }
})();

(function(){
  const ft = document.getElementById("formTime");
  if(ft){
    ft.value = Math.floor(Date.now()/1000);
  }
})();

(function(){
  const form = document.getElementById("contactForm");
  if(!form) return;

  const step1 = form.querySelector('.form-step[data-step="1"]');
  const step2 = form.querySelector('.form-step[data-step="2"]');
  const stepper1 = form.querySelector('.stepper-item[data-step="1"]');
  const stepper2 = form.querySelector('.stepper-item[data-step="2"]');

  const btnNext = document.getElementById("btnNext");
  const btnPrev = document.getElementById("btnPrev");
  const stepCompleted = document.getElementById("stepCompleted");

  function setStep(n){
    if(n === 1){
      step1.classList.add("is-active");
      step2.classList.remove("is-active");
      stepper1.classList.add("is-active");
      stepper2.classList.remove("is-active");
    } else {
      step1.classList.remove("is-active");
      step2.classList.add("is-active");
      stepper1.classList.remove("is-active");
      stepper2.classList.add("is-active");
    }
  }

  function validateStep1(){
    const required = ["name","email","city"];
    let ok = true;
    required.forEach(name => {
      const el = form.querySelector(`[name="${name}"]`);
      if(!el) return;
      el.classList.remove("input-error");
      if(!el.value.trim()){
        ok = false;
        el.classList.add("input-error");
      }
    });
    return ok;
  }

  btnNext && btnNext.addEventListener("click", function(){
    if(validateStep1()){
      stepCompleted.value = "1";
      setStep(2);
      const msg = form.querySelector('[name="message"]');
      if(msg) msg.focus();
    }
  });

  btnPrev && btnPrev.addEventListener("click", function(){
    setStep(1);
  });

  form.addEventListener("submit", function(e){
    // Optional: ensure step 1 done
    if(stepCompleted.value !== "1"){
      e.preventDefault();
      setStep(1);
    }
  });

})();