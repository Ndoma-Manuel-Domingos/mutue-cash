<template>
  <MainLayouts> 
  
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Validação de fechos de caixas</h1>
          </div>
          <div class="col-sm-6">
            <button class="btn btn-dark float-right mr-1" type="button" @click="voltarPaginaAnterior">
              <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
            </button>
          </div>
          <!-- voltarPaginaAnterior() {
            window.history.back();
          }, -->
        </div>
      </div>
    </div>
    
    
    <div class="content">
      <div class="container-fluid">
      
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <form action="">
                <div class="card-body">
                  <div class="row">
                  
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Operadores</label>
                        <select v-model="operador" class="form-control">
                          <option value="">TODOS</option>
                          <option
                            v-for="item in utilizadores"
                            :key="item"
                            :value="item.utilizadores.codigo_importado"
                          >
                            {{ item.utilizadores.nome }}
                          </option>
                        </select>
                      </div>
                    </div>
                    
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="caixa_id">Caixas</label>
                        <select v-model="caixa_id" id="caixa_id" class="form-control">
                          <option value="">TODOS</option>
                          <option
                            v-for="item in caixas"
                            :key="item.codigo"
                            :value="item.codigo"
                          >
                            {{ item.nome }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Data Inicio</label>
                        <input
                          type="date"
                          placeholder="informe do Inicio"
                          class="form-control"
                          v-model="data_inicio"
                        />
                      </div>
                    </div>

                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Data Final</label>
                        <input
                          type="date"
                          placeholder="informe do final"
                          class="form-control"
                          v-model="data_final"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                
                
                <button
                  class="btn btn-success float-right mr-1"
                  type="button"
                  @click="imprimirEXCEL"
                >
                  <i class="fas fa-file-excel"></i>
                  EXCEL
                </button>
                
                <button
                  class="btn btn-danger float-right mr-1"
                  type="button"
                  @click="imprimirPDF"
                >
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº</th>
                      <th>Operador</th>
                      <th>Caixa</th>
                      <th>Estado Caixa</th>
                      <th>Validação</th>
                      <th>Motivo</th>
                      <th>V.Abertura</th>
                      <th>V.Pagamentos</th>
                      <th>V.Depositos</th>
                      <th>Total Fecho</th>
                      <th>Data</th>
                      <th class="text-center">Acções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in items.data" :key="item.codigo">
                      <td>{{ item.codigo ?? ''}}</td>
                      <td>{{ item.operador.nome ?? '' }}</td>
                      <td>{{ item.caixa.nome ?? '' }} </td>
                      <td class="text-uppercase">{{ item.status ?? '' }}</td>
                      <td class="text-uppercase">{{ item.status_admin ?? '' }}</td>
                      <td class="text-uppercase text-center text-danger">{{ item.motivo_rejeicao ?? 'Nenhum motivo' }}</td>
                      <td>{{ formatValor(item.valor_abertura ?? 0)}}</td>
                      <td>{{ formatValor(item.valor_arrecadado_pagamento ?? 0)}}</td>
                      <td>{{ formatValor(item.valor_arrecadado_depositos ?? 0)}}</td>
                      <td>{{ formatValor(item.valor_arrecadado_total ?? 0)}}</td>
                      <td>{{ item.created_at }}</td>
                      <td class="text-center">
                        <Link class="btn" @click="cancelarFecho(item)">
                          <i class="fas fa-ban text-danger"></i>
                        </Link>
                        <Link class="btn" @click="validarFecho(item)">
                          <i class="fas fa-check text-success"></i>
                        </Link>
                        <Link class="btn" @click="imprimirComprovativo(item)">
                          <i class="fas fa-print text-info"></i>
                        </Link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                TOTAL REGISTROS: {{ items.data.length }}
                </Link>
                <Paginacao :links="items.links"
                    :prev="items.prev_page_url"
                    :next="items.next_page_url" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  
  </MainLayouts>
</template>
    
    
  
<script>
  import { sweetSuccess, sweetError } from "../../../components/Alert";
  import Paginacao from "../../../Shared/Paginacao"
  import { Link } from "@inertiajs/inertia-vue3";

  
  export default {
    props: ["items", "utilizadores", "caixas"],
    components: { Link, Paginacao },
    data() {
      return { 
      
        data_inicio: new Date().toISOString().substr(0, 10),
        data_final: new Date().toISOString().substr(0, 10),
        operador: "",
        caixa_id: "",
        params: {},
    };
  },
  
  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      }else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },
    operador: function (val) {
      this.params.operador = val;
      this.updateData();
    },
    caixa_id: function (val) {
      this.params.caixa_id = val;
      this.updateData();
    },
    data_inicio: function (val) {
      this.params.data_inicio = val;
      this.updateData();
    },
    data_final: function (val) {
      this.params.data_final = val;
      this.updateData();
    },
  },
  
  methods: {
    
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/movimentos/validar-fecho", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
    
    cancelarFecho(item) {
        Swal.fire({
          title: 'Atenção!',
          text: "Tem certeza que deseja cancelar este fecho?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sim, desejo!'
        }).then((result) => {
          if (result.isConfirmed) {
            
            Swal.fire({
              title: 'Informe a sua senha para poder continuar com esta operação!',
              input: 'password',
              inputAttributes: {
                autocapitalize: 'off'
              },
              showCancelButton: true,
              confirmButtonText: 'Confirmar',
              showLoaderOnConfirm: true,
              preConfirm: (login) => {
                return fetch(`/movimentos/confirmar-senhar-admin/${login}`)
                  .then(response => {
                    if (!response.ok) {
                      throw new Error(response.statusText)
                    }
                    return response.json()
                  })
                  .catch(error => {
                    Swal.showValidationMessage(
                      `Request failed: ${error}`
                    )
                  })
              },
              allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
              if (result.isConfirmed) {
                Swal.fire({
                  title: 'Informe o motivo da rejeição ou pelo qual desejas cancelar o fecho!',
                  input: 'text',
                  type: 'text',
                  inputAttributes: {
                    autocapitalize: 'off'
                  },
                  preConfirm: (login) => {
                    return fetch(`/movimentos/validar-fecho/${item.codigo}/${login}/cancelar`).then(response => {
                      Swal.fire({
                        title: "Bom Trabalho",
                        text: "Fecho de Caixa Não Validado!",
                        icon: "success",
                        confirmButtonColor: "#3d5476",
                        confirmButtonText: "Ok",
                        onClose: () => {},
                      });
                      this.$Progress.finish();
                      this.updateData();
                    }).catch(error => {
                      Swal.showValidationMessage(`Request failed: ${error}`)
                      this.$Progress.fail();
                    })
                  },
                  allowOutsideClick: () => !Swal.isLoading()
                })
              }
            })
            
            
          }
        })
    },
    
    validarFecho(item) {
        this.$Progress.start();
        Swal.fire({
          title: 'Atenção!',
          text: "Tem certeza que deseja validar este fecho?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sim, desejo!'
        }).then((result) => {
          if (result.isConfirmed) {
          
            Swal.fire({
              title: 'Informe a sua senha para poder continuar com esta operação!',
              input: 'password',
              inputAttributes: {
                autocapitalize: 'off'
              },
              showCancelButton: true,
              confirmButtonText: 'Confirmar',
              showLoaderOnConfirm: true,
              preConfirm: (login) => {
                return fetch(`/movimentos/confirmar-senhar-admin/${login}`)
                  .then(response => {
                    console.log(response)
                    if (!response.ok) {
                        this.$Progress.fail();
                      throw new Error(response.statusText)
                    }
                    return response.json()
                  })
                  .catch(error => {
                    Swal.showValidationMessage(
                      `Request failed: ${error}`
                    )
                    this.$Progress.fail();
                })
              },
              allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
              if (result.isConfirmed) {
                return fetch(`/movimentos/validar-fecho/${item.codigo}/validar`)
                  .then(response => {
                        Swal.fire({
                          title: "Bom Trabalho",
                          text: "Fecho de Caixa Validado com suceeso!",
                          icon: "success",
                          confirmButtonColor: "#3d5476",
                          confirmButtonText: "Ok",
                          onClose: () => {},
                        });
                        this.$Progress.finish();
                        this.updateData();
                  })
                  .catch(error => {
                    Swal.showValidationMessage(
                      `Request failed: ${error}`
                    )
                    this.$Progress.fail();
                })
                
              }
            })
            
          }
        })
    },

    imprimirPDF() {
      window.open(`/movimentos/imprimir-pdf?operador_id=${this.operador}&caixa_id=${this.caixa_id}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`, "_blank");
    },
    
    imprimirEXCEL() {
      window.open(`/movimentos/imprimir-excel?operador_id=${this.operador}&caixa_id=${this.caixa_id}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`, "_blank");
    },
    
    imprimirComprovativo(item) 
    {
      window.open(`/movimentos/imprimir-comprovativo?codigo=${item.codigo}`, "_blank");
    },
    
    voltarPaginaAnterior() {
      window.history.back();
    },
    
  },
};
</script>
  
  
  