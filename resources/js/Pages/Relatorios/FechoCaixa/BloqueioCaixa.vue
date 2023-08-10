<template>
  <AuthLayouts>
    <body class="hold-transition lockscreen">
      <div class="lockscreen-wrapper">
        <div class="lockscreen-logo">
          <b>MUTUE</b>CASH
          <!-- <Link :href="route('mc.dashboard')"><b>MUTUE</b>CASH</Link> -->
        </div>

        <div class="lockscreen-name" v-if="caixa">{{ caixa.nome }}</div>

        <div class="lockscreen-item">
          <div class="lockscreen-image">
            <img src="~admin-lte/dist/img/user2-160x160.jpg" alt="User Image" />
          </div>

          <form class="lockscreen-credentials" action="" @submit.prevent="submit">
            <div class="input-group">
              <input
                v-model="form.code"
                type="text"
                class="form-control"
                placeholder="INFORME SEU CODIGO: "
              />
              <div class="input-group-append">
                <button type="submit" class="btn">
                  <i class="fas fa-arrow-right text-muted"></i>
                </button>
              </div>
            </div>
          </form>
        </div>

      </div>
    </body>
  </AuthLayouts>
</template>

<script>
    import AuthLayouts from "../../Layouts/AuthLayouts";
    import { Link } from "@inertiajs/inertia-vue3";
    
    export default {
        
        props: [
            "caixa"
        ],
        
        components: {
            Link
        },
        
        data() {
            return {
              form: this.$inertia.form({
                // valor_inicial: this.ultimo_movimento ? this.ultimo_movimento.valor_arrecadado_total : 0,
                caixa_id: this.caixa.codigo,
                code: "",
              }),
        
              params: {},
            };
        },
        methods: {
            async submit() {
              this.$Progress.start();

              this.form.post("/movimentos/bloquear-caixa-store", {
                preverseScroll: true,
                onSuccess: (response) => {
                  this.form.reset();
                  this.$Progress.finish();
        
                  Swal.fire({
                    title: "Bom Trabalho",
                    text: "Abertura do caixa realizado com sucesso!",
                    icon: "error",
                    confirmButtonColor: "#3d5476",
                    confirmButtonText: "Ok",
                    onClose: () => {},
                  });
                  window.location.href = '/dashboard';
                },
                onError: (errors) => {
                  console.log(errors);
                  this.$Progress.fail();
                },
              });
            },
        },
    
        layout: AuthLayouts,
    };
</script>