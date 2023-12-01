<template>
    <MainLayouts>
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-8">
              <h1 class="m-0">Utilizadores</h1>
            </div>
            <div class="col-sm-4">
              <button
                class="btn btn-dark float-right mr-1"
                type="button"
                @click="voltarPaginaAnterior"
              >
                <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
              </button>
            </div>
          </div>
        </div>
      </div>
  
        <div class="content">
            <div class="container-fluid">
              <div class="row">
                <div class="col-12 col-md-12">
                  <div class="card">
                    <div class="card-header">
                    </div>
                    
                    <div class="table-responsive">
                      <table class="table table-hover text-nowrap">
                        <thead>
                          <tr>
                            <th>Nº</th>
                            <th>Codigo</th>
                            <th>Nome</th>
                            <th width="150px">Perfil</th>
                            <th width="50px">Acção</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in utilizadores" :key="item">
                            <td>{{ ++index }}</td>
                            <td>{{ item.codigo_importado ?? item.pk_utilizador }}</td>
                            <td>{{ item.nome ?? "" }}</td>
                            <td> 
                              <template v-for="role in item.roles" :key="role">
                                <span>{{ role.name ?? 'sem Perfil' }} |</span>
                              </template>
                            </td>
                            <td>
                            
                            <a
                              class="btn-sm btn-info mx-1"
                              @click="adicionar_perfil(item)"
                            >
                              <i class="fas fa-redo-alt"></i>
                               Perfil
                            </a>
                            
                            <!-- <a
                              class="btn-sm btn-danger mx-1"
                              @click="remover_perfil(item)"
                            >
                              <i class="fas fa-trash"></i>
                               Perfil
                            </a> -->
                          </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                   
                   <!-- <div class="card-footer">
                    <p><strong>Obs: Sabendo que um utilizador por ter vários perfil, para remover um perfil em especifico do utilizador clica sobre o mesmo perfil!</strong></p>
                   </div> -->
                  </div>
                </div>
              </div>
            </div>
        </div>
    
        <div class="modal fade" id="modelActualizarPerfil">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">
                  Actualizar Perfil do(a): {{ title }}
                </h4>
                <button
                  type="button"
                  class="close"
                  data-dismiss="modal"
                  aria-label="Close"
                >
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <!--  -->
                
                <div class="table-responsive">
                  <table class="table table-hover text-nowrap">
                    <thead>
                      <tr>
                        <th>Perfil</th>
                        <th width="50px" class="text-right">Acção</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template
                        v-for="(item) in roles" :key="item.id"
                      >
                        <tr>
                          <td>{{ item.name ?? "" }}</td>
                          <td class="text-right">
                            <input
                              type="checkbox"
                              v-model="form_perfil.role_id"
                              style="width: 20px; height: 20px"
                              :value="item.id"
                            />
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
                                
              </div>
              <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                  Fechar
                </button>
                <button
                  type="button"
                  class="btn btn-primary"
                  @click="actualizar_perfil"
                >
                  Actualizar Perfil
                </button>
              </div>
            </div>
          </div>
        </div>

    </MainLayouts>
  </template>
      
<script>

  import { Link } from "@inertiajs/inertia-vue3";
  import { sweetSuccess, sweetError } from "../../components/Alert"
  import Paginacao from "../../Shared/Paginacao.vue"
  
  export default {
    props: ["utilizadores", "roles"],
    components: { Link, Paginacao },
    data() {
      return {
        form_perfil: this.$inertia.form({
          user_id: "",
          role_id: [],
        }),
  
        title: "",
      };
    },

    mounted() {
      this.params.data_inicio = this.data_inicio;
    },
  
    watch: {
      options: function (val) {
        this.params.page = val.page;
        this.params.page_size = val.itemsPerPage;
        if (val.sortBy.length != 0) {
          this.params.sort_by = val.sortBy[0];
          this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
        } else {
          this.params.sort_by = null;
          this.params.order_by = null;
        }
        this.updateData();
      },
      operador: function (val) {
        this.params.operador = val;
        this.updateData();
      },
    },
  
    methods: {

      adicionar_perfil(item) {
    
        this.title = item.nome ?? "";
        this.form_perfil.user_id = item.codigo_importado ?? "";

        this.$Progress.start();
        axios
          .get(`/roles/utilizador-perfil/${item.codigo_importado}`, {
            params: {},
          })
          .then((response) => {
              this.form_perfil.role_id = [];
              response.data.utilizador.roles.forEach(role => {
                this.form_perfil.role_id.push(role.id);
              // this.form_perfil.role_id = role.id;
            });
            
            this.$Progress.finish();
          })
          .catch((error) => {
            this.$Progress.fail();
          });
  
        $("#modelActualizarPerfil").modal("show");    
      
      },
      
      remover_perfil(item) {
        
        Swal.fire({
          title: 'Atenção!',
          text: "Têm certeza que desaja remover este perfil ao utilizador?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sim, Excluir!'
        }).then((result) => {
          if (result.isConfirmed) {
            
            this.$Progress.start();
            axios.get(`/roles/utilizador-remover-perfil/${item.codigo_importado}`, {
                params: {},
              })
              .then((response) => {
                
                Swal.fire(
                  'Exluido!',
                  'Perfil excluido com successo',
                  'success'
                )
              
                window.location.reload();
                this.$Progress.finish();
              })
              .catch((error) => {
                this.$Progress.fail();
            });
          
            
          }
        })

      },
  
      actualizar_perfil() {
      
        Swal.fire({
          title: 'Atenção!',
          text: "Têm certeza que desaja adicionar este perfil ao utilizador?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sim, Adicionar!'
        }).then((result) => {
          if (result.isConfirmed) {
            
            this.form_perfil.post("/roles/utilizadores-roles", {
              preverseScroll: true,
              onSuccess: () => {
                this.form_perfil.reset();
                this.$Progress.finish();
                sweetSuccess("Dados salvos com sucesso");
                $("#modelActualizarPerfil").modal("hide");
              },
              onError: (errors) => {
                sweetError("Ocorreu um erro ao Cadastrar Perfil!");
              },
            });
          }
        })
      
      },

      voltarPaginaAnterior() {
        window.history.back();
      },
    },
  };
  </script>
      
      
      