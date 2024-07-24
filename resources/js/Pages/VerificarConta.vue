<template>
    <body class="hold-transition login-page">
        <AuthLayouts>
            <div class="login-box">
                <div class="card ard-outline" style="border-top: 5px solid #889b73;border-right: 5px solid #889b73;">

                    <div class="card-header text-center mb-4 py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary" style="height: 130px;color: #f1af09">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>

                        <h3 class="" style="color: #889b73">MUTUE CASH - CONFIRMAÇÃO DA SUA CONTA!</h3>
                    </div>

                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <h6 class="text-center text-danger pb-3" v-if="form.errors.acesso">Acesso Registro</h6>
                           
                            <div class="col-12 mb-4">
                                <div class="input-group">
                                    <input type="text" v-model="form.codigo_check" :class="{'is-invalid' : form.errors.codigo_check}"  class="form-control form-control-lg text-center" placeholder="Verifica o seu E-mail" />
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-lock"></span>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="form.errors.codigo_check" class="login-box-msg text-danger" >{{ form.errors.codigo_check }}</span>
                            </div>
                           
                            <div class="col-12">
                                <div class="row mt-5">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-light btn-lg btn-block" style="background-color: #889b73">
                                            Confirmar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthLayouts>
    </body>
</template>

<script>
    import AuthLayouts from "./Layouts/AuthLayouts.vue";

    export default {
        layout: AuthLayouts,
        
        data() {
            return {
                form: this.$inertia.form({
                    codigo_check: '',
                }),
            };
        },
        
        methods: {
            submit() {
                this.form.post("/verificacao/conta", {
                    preverseScroll: true,
                    onSuccess: () => {
                      this.form.reset();
                      this.$Progress.finish();
            
                      Swal.fire({
                        title: "Bom Trabalho",
                        text: "Subconta Criada com Sucesso",
                        icon: "success",
                        confirmButtonColor: "#3d5476",
                        confirmButtonText: "Ok",
                        onClose: () => {},
                      });
                      // this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
                    },
                    onError: (errors) => {
                      // this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
                      console.log(errors);
                      this.$Progress.fail();
                    },
                })
            },
        },
        
    };
</script>
