<template>
    <body class="hold-transition login-page"
        style="background-image: url('images/video01.mp4');
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
        ">
        <AuthLayouts>

            <div class="login-box">
                <div class="card card-outline">
                    <!-- <h1 class="text-center pt-3" style="color: #006699">
                        <i class="fas fa-chart-line"></i>
                    </h1>  -->
                    <div class="card-header text-center mb-4 py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-info" style="height: 110px;">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>

                        <h3 class="text-info">MUTUE CASH</h3>
                        <!-- <img src="~admin-lte/dist/img/log1.png"  alt="MUTUE FINANÇA" class="elevation-0" style="opacity: 0.8;width: 200px;height: 100px;"/> -->
                    </div>

                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <h6 class="text-center text-danger pb-3" v-if="form.errors.acesso">Acesso Registro</h6>
                            <div class="col-12 mb-3">
                                <div class="input-group">
                                    <input type="text" v-model="form.email" :class="{'is-invalid' : form.errors.email}" class="form-control" placeholder="Email" />
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-envelope"></span>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="form.errors.email" class="login-box-msg text-danger" >{{ form.errors.email }}</span>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="input-group">
                                    <input type="password" v-model="form.password" :class="{'is-invalid' : form.errors.password}"  class="form-control" placeholder="Password" />
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-lock"></span>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="form.errors.password" class="login-box-msg text-danger" >{{ form.errors.password }}</span>
                            </div>

                            <div class="col-12">
                                <div class="row mt-5">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-info btn-block">
                                            Entrar
                                        </button>
                                    </div>
                                    <!-- <div class="col-8">
                                    </div>
                         -->
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
    };
</script>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3";
    import { getCurrentInstance } from 'vue'

    const form = useForm({
        email: "",
        password: ""
    })

    const internalInstance = getCurrentInstance();

    const submit = () => {
        form.post(route("mc.login.post"), {
            onBefore: () => {
                internalInstance.appContext.config.globalProperties.$Progress.start();
            },
            onSuccess: () => {
                internalInstance.appContext.config.globalProperties.$Progress.finish();
                location.reload();
            },
            onError: () => {
                internalInstance.appContext.config.globalProperties.$Progress.fail();
            }
        })
    }
</script>


<script>
    // Limpar cookies relacionados à sessão
    document.cookie.split(";").forEach(function(cookie) {
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
    });
</script>


<style>
/* Style the video: 100% width and height to cover the entire window */
    #myVideo {
      position: fixed;
      right: 0;
      bottom: 0;
      min-width: 100%;
      min-height: 100%;
    }
</style>

